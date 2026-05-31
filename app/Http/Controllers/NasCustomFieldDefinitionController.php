<?php

namespace App\Http\Controllers;

use App\Models\NasCustomFieldDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NasCustomFieldDefinitionController extends Controller
{
    public function index(): View
    {
        $definitions = NasCustomFieldDefinition::orderBy('position')->orderBy('id')->get();

        return view('settings.nas-fields', compact('definitions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'   => ['required', 'string', 'max:255'],
            'type'    => ['required', 'in:text,textarea,date,boolean,select'],
            'options' => ['nullable', 'string'],
        ]);

        NasCustomFieldDefinition::create([
            'label'    => $data['label'],
            'type'     => $data['type'],
            'options'  => $this->parseOptions($data['type'], $data['options'] ?? null),
            'position' => NasCustomFieldDefinition::max('position') + 1,
        ]);

        return redirect()->route('settings.nas-fields.index')
            ->with('success', 'Champ « ' . $data['label'] . ' » créé.');
    }

    public function update(Request $request, NasCustomFieldDefinition $def): RedirectResponse
    {
        $data = $request->validate([
            'label'   => ['required', 'string', 'max:255'],
            'type'    => ['required', 'in:text,textarea,date,boolean,select'],
            'options' => ['nullable', 'string'],
        ]);

        $def->update([
            'label'   => $data['label'],
            'type'    => $data['type'],
            'options' => $this->parseOptions($data['type'], $data['options'] ?? null),
        ]);

        return redirect()->route('settings.nas-fields.index')
            ->with('success', 'Champ « ' . $def->label . ' » mis à jour.');
    }

    public function destroy(NasCustomFieldDefinition $def): RedirectResponse
    {
        $label = $def->label;
        $def->delete();

        return redirect()->route('settings.nas-fields.index')
            ->with('success', 'Champ « ' . $label . ' » supprimé.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        foreach ($request->ids as $position => $id) {
            NasCustomFieldDefinition::where('id', $id)->update(['position' => $position]);
        }

        return redirect()->route('settings.nas-fields.index');
    }

    private function parseOptions(string $type, ?string $raw): ?array
    {
        if ($type !== 'select' || empty($raw)) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
