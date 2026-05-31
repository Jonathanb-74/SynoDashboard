<?php

namespace App\Http\Controllers;

use App\Models\GlobalAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalAttributeController extends Controller
{
    public function index(): View
    {
        $attributes = GlobalAttribute::orderBy('position')->orderBy('id')->get();

        return view('settings.global-attributes', compact('attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'unit'        => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
        ]);

        GlobalAttribute::create(array_merge($data, [
            'position' => GlobalAttribute::max('position') + 1,
        ]));

        return redirect()->route('settings.global-attributes.index')
            ->with('success', 'Attribut « ' . $data['name'] . ' » créé.');
    }

    public function update(Request $request, GlobalAttribute $attr): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'unit'        => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
        ]);

        $attr->update($data);

        return redirect()->route('settings.global-attributes.index')
            ->with('success', 'Attribut mis à jour.');
    }

    public function destroy(GlobalAttribute $attr): RedirectResponse
    {
        $label = $attr->name;
        $attr->delete();

        return redirect()->route('settings.global-attributes.index')
            ->with('success', 'Attribut « ' . $label . ' » supprimé.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        foreach ($request->ids as $position => $id) {
            GlobalAttribute::where('id', $id)->update(['position' => $position]);
        }

        return redirect()->route('settings.global-attributes.index');
    }
}
