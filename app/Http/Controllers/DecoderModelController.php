<?php

namespace App\Http\Controllers;

use App\Models\DisplayBlock;
use App\Models\DisplayColumn;
use App\Models\DisplayElement;
use App\Models\DisplaySubColumn;
use App\Models\JsonDecoderModel;
use App\Models\NasSnapshot;
use App\Services\JsonDecoderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DecoderModelController extends Controller
{
    public function __construct(private JsonDecoderService $decoderService) {}

    // =========================================================================
    // Model CRUD
    // =========================================================================

    public function index()
    {
        $models = JsonDecoderModel::withCount('blocks')->orderBy('name')->get();

        return view('decoder-models.index', compact('models'));
    }

    public function create()
    {
        return view('decoder-models.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:191|unique:json_decoder_models,name',
            'description' => 'nullable|string',
        ]);

        $model = JsonDecoderModel::create($request->only('name', 'description'));

        return redirect()->route('decoder-models.edit', $model)
            ->with('success', "Le décodeur « {$model->name} » a été créé.");
    }

    public function edit(JsonDecoderModel $decoderModel)
    {
        $decoderModel->load(['blocks.elements.columns.subColumns']);

        return view('decoder-models.edit', compact('decoderModel'));
    }

    public function update(Request $request, JsonDecoderModel $decoderModel)
    {
        $request->validate([
            'name'        => 'required|string|max:191|unique:json_decoder_models,name,' . $decoderModel->id,
            'description' => 'nullable|string',
        ]);

        $decoderModel->update($request->only('name', 'description'));

        return redirect()->route('decoder-models.edit', $decoderModel)
            ->with('success', "Le décodeur « {$decoderModel->name} » a été mis à jour.");
    }

    public function destroy(JsonDecoderModel $decoderModel)
    {
        $name = $decoderModel->name;
        $decoderModel->delete();

        return redirect()->route('decoder-models.index')
            ->with('success', "Le décodeur « {$name} » a été supprimé.");
    }

    // =========================================================================
    // Blocks
    // =========================================================================

    public function storeBlock(Request $request, JsonDecoderModel $decoderModel)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
        ]);

        $data['decoder_model_id'] = $decoderModel->id;
        $data['sort_order'] = DisplayBlock::where('decoder_model_id', $decoderModel->id)->max('sort_order') + 1;

        DisplayBlock::create($data);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Bloc ajouté.');
    }

    public function updateBlock(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
        ]);

        $block->update($data);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', "Bloc « {$block->title} » mis à jour.");
    }

    public function destroyBlock(JsonDecoderModel $decoderModel, DisplayBlock $block)
    {
        $block->delete();
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Bloc supprimé.');
    }

    public function reorderBlocks(Request $request, JsonDecoderModel $decoderModel)
    {
        foreach ($request->input('ids', []) as $index => $id) {
            DisplayBlock::where('id', $id)
                ->where('decoder_model_id', $decoderModel->id)
                ->update(['sort_order' => $index]);
        }

        $this->invalidateDecoderCache($decoderModel);

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // Elements
    // =========================================================================

    public function storeElement(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block)
    {
        $data = $request->validate([
            'type'               => 'required|in:simple,loop',
            'label'              => 'required|string|max:191',
            'api_name'           => 'nullable|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $block->elements()->create([
            'type'               => $data['type'],
            'label'              => $data['label'],
            'api_name'           => $data['api_name'] ?? null,
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'internal_key'       => (string) Str::uuid(),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
            'sort_order'         => $block->elements()->max('sort_order') + 1,
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Élément ajouté.');
    }

    public function updateElement(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element)
    {
        $data = $request->validate([
            'type'               => 'required|in:simple,loop',
            'label'              => 'required|string|max:191',
            'api_name'           => 'nullable|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $element->update([
            'type'               => $data['type'],
            'label'              => $data['label'],
            'api_name'           => $data['api_name'] ?? null,
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', "Élément « {$element->label} » mis à jour.");
    }

    public function destroyElement(JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element)
    {
        $element->delete();
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Élément supprimé.');
    }

    public function reorderElements(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block)
    {
        foreach ($request->input('ids', []) as $index => $id) {
            DisplayElement::where('id', $id)
                ->where('block_id', $block->id)
                ->update(['sort_order' => $index]);
        }

        $this->invalidateDecoderCache($decoderModel);

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // Columns
    // =========================================================================

    public function storeColumn(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element)
    {
        $data = $request->validate([
            'type'               => 'required|in:value,loop',
            'label'              => 'required|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $element->columns()->create([
            'type'               => $data['type'],
            'label'              => $data['label'],
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'internal_key'       => (string) Str::uuid(),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
            'sort_order'         => $element->columns()->max('sort_order') + 1,
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Colonne ajoutée.');
    }

    public function updateColumn(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column)
    {
        $data = $request->validate([
            'type'               => 'required|in:value,loop',
            'label'              => 'required|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $column->update([
            'type'               => $data['type'],
            'label'              => $data['label'],
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', "Colonne « {$column->label} » mise à jour.");
    }

    public function destroyColumn(JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column)
    {
        $column->delete();
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Colonne supprimée.');
    }

    public function reorderColumns(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element)
    {
        foreach ($request->input('ids', []) as $index => $id) {
            DisplayColumn::where('id', $id)
                ->where('element_id', $element->id)
                ->update(['sort_order' => $index]);
        }

        $this->invalidateDecoderCache($decoderModel);

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // Sub-columns
    // =========================================================================

    public function storeSubColumn(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column)
    {
        $data = $request->validate([
            'label'              => 'required|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $column->subColumns()->create([
            'label'              => $data['label'],
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'internal_key'       => (string) Str::uuid(),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
            'sort_order'         => $column->subColumns()->max('sort_order') + 1,
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Sous-colonne ajoutée.');
    }

    public function updateSubColumn(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column, DisplaySubColumn $subColumn)
    {
        $data = $request->validate([
            'label'              => 'required|string|max:191',
            'json_path'          => 'nullable|string',
            'transformer'        => 'nullable|string|max:50',
            'transformer_config' => 'nullable|string',
        ]);

        $subColumn->update([
            'label'              => $data['label'],
            'json_path'          => $this->parsePath($data['json_path'] ?? ''),
            'transformer'        => $data['transformer'] ?? null,
            'transformer_config' => $this->parseJson($data['transformer_config'] ?? null),
        ]);
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', "Sous-colonne « {$subColumn->label} » mise à jour.");
    }

    public function destroySubColumn(JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column, DisplaySubColumn $subColumn)
    {
        $subColumn->delete();
        $this->invalidateDecoderCache($decoderModel);

        return back()->with('success', 'Sous-colonne supprimée.');
    }

    public function reorderSubColumns(Request $request, JsonDecoderModel $decoderModel, DisplayBlock $block, DisplayElement $element, DisplayColumn $column)
    {
        foreach ($request->input('ids', []) as $index => $id) {
            DisplaySubColumn::where('id', $id)
                ->where('column_id', $column->id)
                ->update(['sort_order' => $index]);
        }

        $this->invalidateDecoderCache($decoderModel);

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function invalidateDecoderCache(JsonDecoderModel $decoderModel): void
    {
        $decoderModel->touch();

        NasSnapshot::whereHas('nas', fn($q) => $q->where('decoder_model_id', $decoderModel->id))
            ->whereNotNull('decoded_cache')
            ->update(['decoded_cache' => null]);
    }

    private function parsePath(string $path): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $path)),
            fn($v) => $v !== ''
        ));
    }

    private function parseJson(?string $json): ?array
    {
        if (!$json || trim($json) === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
