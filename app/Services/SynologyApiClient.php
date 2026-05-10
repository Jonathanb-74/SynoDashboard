<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class SynologyApiClient
{
    private Client $httpClient;
    private string $baseUrl;
    private ?string $sid = null;
    private ?array $cachedApiInfo = null;

    private static array $synologyErrors = [
        100 => 'Unknown error',
        101 => 'Invalid parameters',
        102 => 'API does not exist',
        103 => 'Method does not exist',
        104 => 'Version does not support the functionality',
        105 => 'The logged in session does not have permission',
        106 => 'Session timeout',
        107 => 'Session interrupted by duplicate login',
        400 => 'Invalid credentials',
        401 => 'Guest or disabled account',
        402 => 'Permission denied',
        403 => '2-step verification code required',
        404 => 'Failed to authenticate 2-step verification code',
    ];

    public function connect(string $url, bool $sslVerify = true): void
    {
        $this->baseUrl = rtrim($url, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'verify'   => $sslVerify,
            'timeout'  => config('synology.timeout', 30),
            'headers'  => ['Accept' => 'application/json'],
        ]);
        $this->sid = null;
        $this->cachedApiInfo = null;
    }

    public function getApiInfo(): array
    {
        if ($this->cachedApiInfo !== null) {
            return $this->cachedApiInfo;
        }

        $data = $this->request('query.cgi', [
            'api'     => 'SYNO.API.Info',
            'version' => '1',
            'method'  => 'query',
            'query'   => 'all',
        ]);

        $this->cachedApiInfo = $data;

        return $this->cachedApiInfo;
    }

    public function authenticate(string $username, string $password): string
    {
        $authPath = $this->getAuthPath();

        $data = $this->request($authPath, [
            'api'     => 'SYNO.API.Auth',
            'version' => '6',
            'method'  => 'login',
            'account' => $username,
            'passwd'  => $password,
            'session' => config('synology.session_name', 'SynoManager'),
            'format'  => 'sid',
        ]);

        $this->sid = $data['sid'];

        return $this->sid;
    }

    public function logout(): void
    {
        if ($this->sid === null) {
            return;
        }

        try {
            $authPath = $this->getAuthPath();
            $this->httpClient->get('/webapi/' . $authPath, [
                'query' => [
                    'api'     => 'SYNO.API.Auth',
                    'version' => '6',
                    'method'  => 'logout',
                    'session' => config('synology.session_name', 'SynoManager'),
                    '_sid'    => $this->sid,
                ],
            ]);
        } catch (\Throwable) {
            // Logout errors are intentionally ignored
        } finally {
            $this->sid = null;
        }
    }

    public function callApi(string $apiName, string $method = 'get', array $params = [], ?int $version = null): array
    {
        $apiInfo = $this->getApiInfo();

        if (!isset($apiInfo[$apiName])) {
            throw new RuntimeException("API '{$apiName}' not found on this NAS.");
        }

        $entry  = $apiInfo[$apiName];
        $path   = $entry['path'];
        $version = $version ?? min((int) ($entry['maxVersion'] ?? 1), 99);

        $query = array_merge([
            'api'     => $apiName,
            'version' => (string) $version,
            'method'  => $method,
        ], $params);

        if ($this->sid !== null) {
            $query['_sid'] = $this->sid;
        }

        return $this->request($path, $query);
    }

    public function callApiDirect(string $apiName, string $path, string $method, int $version, array $params = []): array
    {
        $query = array_merge([
            'api'     => $apiName,
            'version' => (string) $version,
            'method'  => $method,
        ], $params);

        if ($this->sid !== null) {
            $query['_sid'] = $this->sid;
        }

        return $this->request($path, $query);
    }

    // Method and version to use for each standard API (from working reference script)
    private static array $standardApiConfig = [
        'SYNO.Core.System'         => ['method' => 'info',      'version' => 1],
        'SYNO.Core.Network'        => ['method' => 'get',        'version' => 1],
        'SYNO.Storage.CGI.Storage' => ['method' => 'load_info',  'version' => 1],
        'SYNO.Core.Package'        => ['method' => 'list',       'version' => 1],
        'SYNO.Core.Upgrade'        => ['method' => 'check',      'version' => 1],
    ];

    public function collectStandard(array $availableApis): array
    {
        $responses = [];

        foreach (config('synology.standard_apis', []) as $apiName) {
            if (!isset($availableApis[$apiName])) {
                continue;
            }

            $cfg    = self::$standardApiConfig[$apiName] ?? ['method' => 'get', 'version' => 1];
            $method  = $cfg['method'];
            $version = $cfg['version'];

            try {
                $responses[$apiName] = $this->callApi($apiName, $method, [], $version);
            } catch (RuntimeException $e) {
                $responses[$apiName] = ['error' => $e->getMessage()];
            }
        }

        return $responses;
    }

    public function buildStandardPayload(
        array $nasIdentifier,
        array $apiList,
        array $responses
    ): array {
        return [
            'agent_version'  => 'test-console',
            'collected_at'   => now()->toIso8601String(),
            'nas_identifier' => $nasIdentifier,
            'api_list'       => $apiList,
            'responses'      => $responses,
        ];
    }

    private function getAuthPath(): string
    {
        $apiInfo = $this->getApiInfo();

        if (!isset($apiInfo['SYNO.API.Auth'])) {
            throw new RuntimeException('SYNO.API.Auth not found in API info.');
        }

        return $apiInfo['SYNO.API.Auth']['path'];
    }

    private function request(string $path, array $params): array
    {
        // Synology expects array-typed params (compound, additional, …) as JSON strings
        $form = array_map(
            fn($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $v,
            $params
        );

        try {
            $response = $this->httpClient->post('/webapi/' . $path, ['form_params' => $form]);
            $body     = (string) $response->getBody();
            $decoded  = json_decode($body, true);

            if (!is_array($decoded)) {
                throw new RuntimeException('Invalid JSON response from NAS.');
            }

            if (!($decoded['success'] ?? false)) {
                $code    = $decoded['error']['code'] ?? 0;
                $message = self::$synologyErrors[$code] ?? "Synology error code {$code}";
                throw new RuntimeException($message);
            }

            return $decoded['data'] ?? [];
        } catch (GuzzleException $e) {
            throw new RuntimeException('Network error: ' . $e->getMessage(), 0, $e);
        }
    }
}
