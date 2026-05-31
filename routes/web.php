<?php

use App\Http\Controllers\ApiDebugController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NasCustomFieldDefinitionController;
use App\Http\Controllers\NasViewTableController;
use App\Http\Controllers\DashboardWidgetController;
use App\Http\Controllers\ApiLogController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\SmtpSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMethodOptionController;
use App\Http\Controllers\ApiModelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecoderModelController;
use App\Http\Controllers\NasApprovalController;
use App\Http\Controllers\NasController;
use App\Http\Controllers\NasTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SnapshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // NAS approval — must be declared before nas/{nas} to avoid conflict
    Route::get('/nas/pending',           [NasApprovalController::class, 'index'])->name('nas.pending');
    Route::post('/nas/{nas}/approve',    [NasApprovalController::class, 'approve'])->name('nas.approve');
    Route::post('/nas/{nas}/reject',     [NasApprovalController::class, 'reject'])->name('nas.reject');

    // NAS CRUD
    Route::resource('nas', NasController::class)->only(['index', 'show', 'update', 'destroy'])->parameters(['nas' => 'nas']);
    Route::post('/nas/{nas}/redecode',        [NasController::class, 'redecode'])->name('nas.redecode');
    Route::post('/nas/{nas}/custom-fields',   [NasController::class, 'updateCustomFields'])->name('nas.custom-fields.update');
    Route::post('/nas/{nas}/regenerate-hmac', [NasController::class, 'regenerateHmac'])->name('nas.regenerate-hmac');

    // Snapshots
    Route::get('/snapshots/{snapshot}',      [SnapshotController::class, 'show'])->name('snapshots.show');
    Route::get('/snapshots/{snapshot}/raw',  [SnapshotController::class, 'raw'])->name('snapshots.raw');

    // Test console
    Route::get('/test',       [NasTestController::class, 'index'])->name('test.index');
    Route::post('/test/run',  [NasTestController::class, 'run'])->name('test.run');

    // API models
    Route::resource('api-models', ApiModelController::class);
    Route::post('api-models/{apiModel}/create-decoder',  [ApiModelController::class, 'createDecoder'])->name('api-models.create-decoder');
    Route::post('api-models/{apiModel}/duplicate',       [ApiModelController::class, 'duplicate'])->name('api-models.duplicate');
    Route::post('api-models/{apiModel}/propagate-entry', [ApiModelController::class, 'propagateEntry'])->name('api-models.propagate-entry');

    // Decoder models
    Route::resource('decoder-models', DecoderModelController::class)->except('show');
    Route::post('decoder-models/{decoderModel}/duplicate', [DecoderModelController::class, 'duplicate'])->name('decoder-models.duplicate');
    Route::post('decoder-models/{decoderModel}/copy-to',   [DecoderModelController::class, 'copyTo'])->name('decoder-models.copy-to');

    // Blocks (declare reorder before {block} to avoid conflict)
    Route::post('decoder-models/{decoderModel}/blocks/reorder',
        [DecoderModelController::class, 'reorderBlocks'])->name('decoder-models.reorderBlocks');
    Route::post('decoder-models/{decoderModel}/blocks',
        [DecoderModelController::class, 'storeBlock'])->name('decoder-models.storeBlock');
    Route::patch('decoder-models/{decoderModel}/blocks/{block}',
        [DecoderModelController::class, 'updateBlock'])->name('decoder-models.updateBlock');
    Route::delete('decoder-models/{decoderModel}/blocks/{block}',
        [DecoderModelController::class, 'destroyBlock'])->name('decoder-models.destroyBlock');

    // Elements
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements/reorder',
        [DecoderModelController::class, 'reorderElements'])->name('decoder-models.reorderElements');
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements',
        [DecoderModelController::class, 'storeElement'])->name('decoder-models.storeElement');
    Route::patch('decoder-models/{decoderModel}/blocks/{block}/elements/{element}',
        [DecoderModelController::class, 'updateElement'])->name('decoder-models.updateElement');
    Route::delete('decoder-models/{decoderModel}/blocks/{block}/elements/{element}',
        [DecoderModelController::class, 'destroyElement'])->name('decoder-models.destroyElement');

    // Columns
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/reorder',
        [DecoderModelController::class, 'reorderColumns'])->name('decoder-models.reorderColumns');
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns',
        [DecoderModelController::class, 'storeColumn'])->name('decoder-models.storeColumn');
    Route::patch('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}',
        [DecoderModelController::class, 'updateColumn'])->name('decoder-models.updateColumn');
    Route::delete('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}',
        [DecoderModelController::class, 'destroyColumn'])->name('decoder-models.destroyColumn');

    // Sub-columns
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}/sub-columns/reorder',
        [DecoderModelController::class, 'reorderSubColumns'])->name('decoder-models.reorderSubColumns');
    Route::post('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}/sub-columns',
        [DecoderModelController::class, 'storeSubColumn'])->name('decoder-models.storeSubColumn');
    Route::patch('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}/sub-columns/{subColumn}',
        [DecoderModelController::class, 'updateSubColumn'])->name('decoder-models.updateSubColumn');
    Route::delete('decoder-models/{decoderModel}/blocks/{block}/elements/{element}/columns/{column}/sub-columns/{subColumn}',
        [DecoderModelController::class, 'destroySubColumn'])->name('decoder-models.destroySubColumn');

    // Docs
    Route::get('/docs/agent-api', [DocsController::class, 'agentApi'])->name('docs.agent-api');

    // Import / Export
    Route::get('/import-export',                    [ImportExportController::class, 'index'])->name('import-export.index');
    Route::post('/import-export/export',            [ImportExportController::class, 'export'])->name('import-export.export');
    Route::post('/import-export/import',            [ImportExportController::class, 'import'])->name('import-export.import');
    Route::post('/import-export/import/confirm',    [ImportExportController::class, 'importConfirm'])->name('import-export.import.confirm');
    Route::post('/import-export/import/cancel',     [ImportExportController::class, 'importCancel'])->name('import-export.import.cancel');

    // API logs
    Route::get('/api-logs',              [ApiLogController::class, 'index'])->name('api-logs.index');
    Route::get('/api-logs/{apiLog}',     [ApiLogController::class, 'show'])->name('api-logs.show');
    Route::delete('/api-logs',           [ApiLogController::class, 'destroy'])->name('api-logs.destroy');

    // API method debug
    Route::get('/debug/api-method',          [ApiDebugController::class, 'index'])->name('debug.api-method.index');
    Route::post('/debug/api-method/probe',   [ApiDebugController::class, 'probe'])->name('debug.api-method.probe');
    Route::post('/debug/api-method/apply',   [ApiDebugController::class, 'apply'])->name('debug.api-method.apply');

    // Admin-only routes
    Route::middleware('admin')->group(function () {

        // User management
        Route::get('/users',              [UserController::class, 'index'])->name('users.index');
        Route::post('/users',             [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',    [UserController::class, 'destroy'])->name('users.destroy');

        // Invitations (admin sends)
        Route::post('/invitations',                [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

        // Settings
        Route::get('/settings/api-methods',                                    [ApiMethodOptionController::class, 'index'])->name('settings.api-methods.index');
        Route::post('/settings/api-methods/save-all',                          [ApiMethodOptionController::class, 'saveAll'])->name('settings.api-methods.save-all');
        Route::post('/settings/api-methods',                                   [ApiMethodOptionController::class, 'store'])->name('settings.api-methods.store');
        Route::delete('/settings/api-methods/{apiMethodOption}',               [ApiMethodOptionController::class, 'destroy'])->name('settings.api-methods.destroy');

        Route::get('/settings/smtp',       [SmtpSettingController::class, 'edit'])->name('settings.smtp.edit');
        Route::post('/settings/smtp',      [SmtpSettingController::class, 'update'])->name('settings.smtp.update');
        Route::post('/settings/smtp/test', [SmtpSettingController::class, 'test'])->name('settings.smtp.test');

        // NAS custom fields definitions
        Route::get('/settings/nas-fields',                       [NasCustomFieldDefinitionController::class, 'index'])->name('settings.nas-fields.index');
        Route::post('/settings/nas-fields',                      [NasCustomFieldDefinitionController::class, 'store'])->name('settings.nas-fields.store');
        Route::patch('/settings/nas-fields/{def}',               [NasCustomFieldDefinitionController::class, 'update'])->name('settings.nas-fields.update');
        Route::delete('/settings/nas-fields/{def}',              [NasCustomFieldDefinitionController::class, 'destroy'])->name('settings.nas-fields.destroy');
        Route::post('/settings/nas-fields/reorder',              [NasCustomFieldDefinitionController::class, 'reorder'])->name('settings.nas-fields.reorder');

        // NAS view tables
        Route::get('/settings/nas-views',                                          [NasViewTableController::class, 'index'])->name('settings.nas-views.index');
        Route::post('/settings/nas-views',                                         [NasViewTableController::class, 'store'])->name('settings.nas-views.store');
        Route::patch('/settings/nas-views/{view}',                                 [NasViewTableController::class, 'update'])->name('settings.nas-views.update');
        Route::delete('/settings/nas-views/{view}',                                [NasViewTableController::class, 'destroy'])->name('settings.nas-views.destroy');
        Route::post('/settings/nas-views/{view}/columns',                          [NasViewTableController::class, 'storeColumn'])->name('settings.nas-views.columns.store');
        Route::delete('/settings/nas-views/{view}/columns/{col}',                  [NasViewTableController::class, 'destroyColumn'])->name('settings.nas-views.columns.destroy');
        Route::post('/settings/nas-views/{view}/columns/reorder',                  [NasViewTableController::class, 'reorderColumns'])->name('settings.nas-views.columns.reorder');

        // Dashboard widgets
        Route::get('/settings/dashboard-widgets',                                  [DashboardWidgetController::class, 'index'])->name('settings.dashboard-widgets.index');
        Route::post('/settings/dashboard-widgets',                                 [DashboardWidgetController::class, 'store'])->name('settings.dashboard-widgets.store');
        Route::patch('/settings/dashboard-widgets/{widget}',                       [DashboardWidgetController::class, 'update'])->name('settings.dashboard-widgets.update');
        Route::delete('/settings/dashboard-widgets/{widget}',                      [DashboardWidgetController::class, 'destroy'])->name('settings.dashboard-widgets.destroy');
        Route::post('/settings/dashboard-widgets/reorder',                         [DashboardWidgetController::class, 'reorder'])->name('settings.dashboard-widgets.reorder');

    });

    // Breeze profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Invitation acceptance — guest routes (no auth required)
Route::get('/invitation/{token}',  [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

require __DIR__.'/auth.php';
