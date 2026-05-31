<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SmtpSettingController extends Controller
{
    private const KEYS = [
        'mail.mailers.smtp.host',
        'mail.mailers.smtp.port',
        'mail.mailers.smtp.username',
        'mail.mailers.smtp.password',
        'mail.mailers.smtp.encryption',
        'mail.from.address',
        'mail.from.name',
        'mail.default',
    ];

    public function edit()
    {
        $settings = AppSetting::whereIn('key', self::KEYS)
            ->pluck('value', 'key')
            ->toArray();

        return view('settings.smtp', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mail.mailers.smtp.host'       => 'nullable|string|max:255',
            'mail.mailers.smtp.port'       => 'nullable|integer|min:1|max:65535',
            'mail.mailers.smtp.username'   => 'nullable|string|max:255',
            'mail.mailers.smtp.password'   => 'nullable|string|max:255',
            'mail.mailers.smtp.encryption' => 'nullable|in:tls,ssl,starttls,',
            'mail.from.address'            => 'nullable|email|max:255',
            'mail.from.name'               => 'nullable|string|max:255',
        ]);

        AppSetting::setMany($data['mail'] ? $this->flattenDotKeys('mail', $data['mail']) : []);

        // Always activate SMTP as default mailer
        AppSetting::set('mail.default', 'smtp');

        // Apply immediately to the running config
        config(['mail.default' => 'smtp']);
        foreach (self::KEYS as $key) {
            config([$key => AppSetting::get($key) ?: null]);
        }

        // Purge cached mailer so next send picks up new settings
        Mail::purge('smtp');

        return back()->with('success', 'Configuration SMTP enregistrée.');
    }

    public function test(Request $request)
    {
        $to = $request->validate([
            'to' => 'required|email',
        ])['to'];

        try {
            // Purge cached mailer to force use of current DB config
            Mail::purge('smtp');

            Mail::mailer('smtp')->raw(
                'Ceci est un email de test envoyé depuis SynoManager.',
                fn($m) => $m->to($to)->subject('[SynoManager] Test SMTP')
            );

            return back()->with('success', "Email de test envoyé à « {$to} ».");
        } catch (\Throwable $e) {
            return back()->withErrors(['smtp_test' => 'Échec : ' . $e->getMessage()]);
        }
    }

    private function flattenDotKeys(string $prefix, array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix . '.' . $key;
            if (is_array($value)) {
                $result += $this->flattenDotKeys($fullKey, $value);
            } else {
                $result[$fullKey] = $value;
            }
        }
        return $result;
    }
}
