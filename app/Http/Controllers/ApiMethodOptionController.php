<?php

namespace App\Http\Controllers;

use App\Models\ApiMethodOption;
use Illuminate\Http\Request;

class ApiMethodOptionController extends Controller
{
    public function index()
    {
        $methods = ApiMethodOption::orderBy('sort_order')->orderBy('name')->get();
        return view('settings.api-methods', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:api_method_options,name|regex:/^[a-z_]+$/',
        ], [
            'name.regex' => 'Le nom ne peut contenir que des lettres minuscules et des underscores.',
        ]);

        $maxOrder = ApiMethodOption::max('sort_order') ?? 0;

        ApiMethodOption::create([
            'name'       => $request->input('name'),
            'sort_order' => $maxOrder + 10,
        ]);

        return back()->with('success', "Méthode « {$request->input('name')} » ajoutée.");
    }

    public function saveAll(Request $request)
    {
        $order = json_decode($request->input('order_json', '[]'), true) ?? [];
        $debug = array_map('intval', $request->input('debug', []));

        ApiMethodOption::all()->each(function (ApiMethodOption $m) use ($order, $debug) {
            $pos = array_search((string) $m->id, $order);
            $m->update([
                'sort_order'    => $pos !== false ? ($pos + 1) * 10 : $m->sort_order,
                'debug_enabled' => in_array($m->id, $debug),
            ]);
        });

        return back()->with('success', 'Paramètres des méthodes enregistrés.');
    }

    public function update(Request $request, ApiMethodOption $apiMethodOption)
    {
        $apiMethodOption->update([
            'debug_enabled' => $request->boolean('debug_enabled'),
        ]);
        return response()->json(['ok' => true]);
    }

    public function destroy(ApiMethodOption $apiMethodOption)
    {
        $name = $apiMethodOption->name;
        $apiMethodOption->delete();
        return back()->with('success', "Méthode « {$name} » supprimée.");
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:api_method_options,id']);

        foreach ($request->input('order') as $i => $id) {
            ApiMethodOption::where('id', $id)->update(['sort_order' => ($i + 1) * 10]);
        }

        return response()->json(['ok' => true]);
    }
}
