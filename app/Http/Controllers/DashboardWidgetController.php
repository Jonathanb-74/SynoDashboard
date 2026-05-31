<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardWidgetController extends Controller
{
    public function index(): View
    {
        $widgets = DashboardWidget::orderBy('position')->get();

        return view('settings.dashboard-widgets', compact('widgets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'       => ['required', 'string', 'max:255'],
            'color'       => ['required', 'in:primary,success,warning,danger,info,secondary'],
            'source'      => ['required', 'in:device,custom_field'],
            'field_key'   => ['required', 'string', 'max:64'],
            'field_value' => ['required', 'string', 'max:255'],
        ]);

        DashboardWidget::create([
            'type'        => 'count',
            'label'       => $data['label'],
            'color'       => $data['color'],
            'source'      => $data['source'],
            'field_key'   => $data['field_key'],
            'field_value' => $data['field_value'],
            'active'      => true,
            'position'    => DashboardWidget::max('position') + 1,
        ]);

        return redirect()->route('settings.dashboard-widgets.index')->with('success', 'Widget créé.');
    }

    public function update(Request $request, DashboardWidget $widget): RedirectResponse
    {
        if ($widget->isBuiltin()) {
            $data = $request->validate([
                'label'  => ['required', 'string', 'max:255'],
                'color'  => ['required', 'in:primary,success,warning,danger,info,secondary'],
                'active' => ['boolean'],
            ]);
        } else {
            $data = $request->validate([
                'label'       => ['required', 'string', 'max:255'],
                'color'       => ['required', 'in:primary,success,warning,danger,info,secondary'],
                'active'      => ['boolean'],
                'source'      => ['required', 'in:device,custom_field'],
                'field_key'   => ['required', 'string', 'max:64'],
                'field_value' => ['required', 'string', 'max:255'],
            ]);
        }

        $widget->update(array_merge($data, ['active' => $request->boolean('active')]));

        return redirect()->route('settings.dashboard-widgets.index')->with('success', 'Widget mis à jour.');
    }

    public function destroy(DashboardWidget $widget): RedirectResponse
    {
        abort_if($widget->isBuiltin(), 403, 'Les widgets intégrés ne peuvent pas être supprimés.');
        $widget->delete();

        return redirect()->route('settings.dashboard-widgets.index')->with('success', 'Widget supprimé.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        foreach ($request->ids as $position => $id) {
            DashboardWidget::where('id', $id)->update(['position' => $position]);
        }

        return redirect()->route('settings.dashboard-widgets.index');
    }
}
