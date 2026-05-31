<?php

namespace App\Http\Controllers;

use App\Models\NasCustomFieldDefinition;
use App\Models\NasViewColumn;
use App\Models\NasViewTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NasViewTableController extends Controller
{
    public function index(): View
    {
        $views           = NasViewTable::with('columns')->orderBy('name')->get();
        $customFieldDefs = NasCustomFieldDefinition::orderBy('position')->get();

        return view('settings.nas-views', compact('views', 'customFieldDefs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        NasViewTable::create($data);

        return redirect()->route('settings.nas-views.index')->with('success', 'Vue créée.');
    }

    public function update(Request $request, NasViewTable $view): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $view->update($data);

        // Handle default flags — only one view per type can be default
        foreach (['nas_page' => 'is_nas_page_default', 'dashboard' => 'is_dashboard_default'] as $key => $col) {
            if ($request->boolean($col)) {
                NasViewTable::where('id', '!=', $view->id)->update([$col => false]);
                $view->update([$col => true]);
            } elseif ($request->has($col)) {
                $view->update([$col => false]);
            }
        }

        return redirect()->route('settings.nas-views.index')->with('success', 'Vue mise à jour.');
    }

    public function destroy(NasViewTable $view): RedirectResponse
    {
        $view->delete();

        return redirect()->route('settings.nas-views.index')->with('success', 'Vue supprimée.');
    }

    // ─── Column management ────────────────────────────────────────────────

    public function storeColumn(Request $request, NasViewTable $view): RedirectResponse
    {
        $data = $request->validate([
            'source'    => ['required', 'in:device,custom_field'],
            'field_key' => ['required', 'string', 'max:64'],
            'label'     => ['nullable', 'string', 'max:255'],
        ]);

        // Prevent duplicates
        if ($view->columns()->where('source', $data['source'])->where('field_key', $data['field_key'])->exists()) {
            return back()->with('error', 'Cette colonne est déjà présente dans cette vue.');
        }

        $view->columns()->create([
            'source'    => $data['source'],
            'field_key' => $data['field_key'],
            'label'     => $data['label'] ?: null,
            'position'  => $view->columns()->max('position') + 1,
        ]);

        return redirect()->route('settings.nas-views.index')->with('success', 'Colonne ajoutée.');
    }

    public function destroyColumn(NasViewTable $view, NasViewColumn $col): RedirectResponse
    {
        abort_unless($col->view_id === $view->id, 404);
        $col->delete();

        return redirect()->route('settings.nas-views.index')->with('success', 'Colonne supprimée.');
    }

    public function reorderColumns(Request $request, NasViewTable $view): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        foreach ($request->ids as $position => $id) {
            $view->columns()->where('id', $id)->update(['position' => $position]);
        }

        return redirect()->route('settings.nas-views.index');
    }
}
