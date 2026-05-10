<?php

use App\Http\Controllers\ApiDebugController;
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
    Route::resource('nas', NasController::class)->only(['index', 'show', 'destroy'])->parameters(['nas' => 'nas']);
    Route::post('/nas/{nas}/redecode', [NasController::class, 'redecode'])->name('nas.redecode');

    // Snapshots
    Route::get('/snapshots/{snapshot}',      [SnapshotController::class, 'show'])->name('snapshots.show');
    Route::get('/snapshots/{snapshot}/raw',  [SnapshotController::class, 'raw'])->name('snapshots.raw');

    // Test console
    Route::get('/test',       [NasTestController::class, 'index'])->name('test.index');
    Route::post('/test/run',  [NasTestController::class, 'run'])->name('test.run');

    // API models
    Route::resource('api-models', ApiModelController::class);
    Route::post('api-models/{apiModel}/create-decoder', [ApiModelController::class, 'createDecoder'])->name('api-models.create-decoder');

    // Decoder models
    Route::resource('decoder-models', DecoderModelController::class)->except('show');

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

    // Import / Export
    Route::get('/import-export',                    [ImportExportController::class, 'index'])->name('import-export.index');
    Route::post('/import-export/export',            [ImportExportController::class, 'export'])->name('import-export.export');
    Route::post('/import-export/import',            [ImportExportController::class, 'import'])->name('import-export.import');
    Route::post('/import-export/import/confirm',    [ImportExportController::class, 'importConfirm'])->name('import-export.import.confirm');
    Route::post('/import-export/import/cancel',     [ImportExportController::class, 'importCancel'])->name('import-export.import.cancel');

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

        // Settings
        Route::get('/settings/api-methods',                                    [ApiMethodOptionController::class, 'index'])->name('settings.api-methods.index');
        Route::post('/settings/api-methods/save-all',                          [ApiMethodOptionController::class, 'saveAll'])->name('settings.api-methods.save-all');
        Route::post('/settings/api-methods',                                   [ApiMethodOptionController::class, 'store'])->name('settings.api-methods.store');
        Route::delete('/settings/api-methods/{apiMethodOption}',               [ApiMethodOptionController::class, 'destroy'])->name('settings.api-methods.destroy');

        Route::get('/settings/smtp',       [SmtpSettingController::class, 'edit'])->name('settings.smtp.edit');
        Route::post('/settings/smtp',      [SmtpSettingController::class, 'update'])->name('settings.smtp.update');
        Route::post('/settings/smtp/test', [SmtpSettingController::class, 'test'])->name('settings.smtp.test');

    });

    // Breeze profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
