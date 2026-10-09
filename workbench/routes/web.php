<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\FormKitchenSinkController;
use Workbench\App\Http\Controllers\LayoutSlotsDemoController;
use Workbench\App\Http\Controllers\TableNodeController;

Route::middleware(['craft', 'craft.cp', 'auth', 'can:accessCp'])
    ->prefix('{cpTrigger}/{actionTrigger}')
    ->group(function (): void {
        Route::get('workbench/text-expander-options', [FormKitchenSinkController::class, 'textExpanderOptions']);

        Route::controller(TableNodeController::class)->prefix('workbench/table')->group(function (): void {
            Route::post('data', 'data');
            Route::post('reorder', 'reorder');
            Route::post('move-to-page', 'moveToPage');
            Route::post('delete', 'delete');
            Route::get('delete-modal', 'deleteModal');
            Route::post('set-status', 'setStatus');
            Route::post('set-category', 'setCategory');
            Route::post('duplicate', 'duplicate');
            Route::get('adjust-stock-modal', 'adjustStockModal');
            Route::post('adjust-stock', 'adjustStock');
            Route::post('products/{id}', 'save');
        });
    });

Route::middleware(['craft', 'craft.cp', 'auth', 'can:accessCp'])
    ->prefix('{cpTrigger}')
    ->group(function (): void {
        Route::get('workbench/forms', [FormKitchenSinkController::class, 'index'])
            ->name('workbench.forms.index');
        Route::get('workbench/forms/{type}/{component}', [FormKitchenSinkController::class, 'component'])
            ->whereIn('type', ['controls', 'nodes'])
            ->name('workbench.forms.component');
        Route::get('workbench/forms/{type}/{component}/{renderer}', [FormKitchenSinkController::class, 'show'])
            ->whereIn('type', ['controls', 'nodes'])
            ->whereIn('renderer', ['vue', 'html'])
            ->name('workbench.forms.show');
        Route::get('workbench/layout-slots', LayoutSlotsDemoController::class)
            ->name('workbench.layout-slots');

        Route::controller(TableNodeController::class)->prefix('workbench/table')->group(function (): void {
            Route::get('/', 'index')->name('workbench.table.index');
            Route::get('reset', 'reset')->name('workbench.table.reset');
            Route::get('products/{id}', 'edit')->name('workbench.table.edit');
            Route::get('{story}/{renderer?}', 'show')
                ->whereIn('story', array_keys(TableNodeController::STORIES))
                ->whereIn('renderer', ['vue', 'html'])
                ->name('workbench.table.show');
        });
    });
