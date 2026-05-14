<?php

namespace App\Http\Controllers;

class DocsController extends Controller
{
    public function agentApi()
    {
        $path     = base_path('docs/agent-api.md');
        $markdown = file_exists($path) ? file_get_contents($path) : '*Fichier introuvable.*';

        return view('docs.agent-api', compact('markdown'));
    }
}
