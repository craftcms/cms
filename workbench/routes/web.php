<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\ChipsController;
use Workbench\App\Http\Controllers\LayoutSlotsDemoController;
use Workbench\App\Http\Controllers\UiKitchenSinkController;

Route::middleware(['craft', 'craft.cp', 'auth', 'can:accessCp'])
    ->prefix('{cpTrigger}/{actionTrigger}')
    ->get('workbench/text-expander-options', [UiKitchenSinkController::class, 'textExpanderOptions']);

Route::middleware(['craft', 'craft.cp', 'auth', 'can:accessCp'])
    ->prefix('{cpTrigger}')
    ->group(function (): void {
        Route::get('workbench/ui', [UiKitchenSinkController::class, 'index'])
            ->name('workbench.ui.index');
        Route::get('workbench/ui/{type}/{component}', [UiKitchenSinkController::class, 'component'])
            ->whereIn('type', ['controls', 'nodes'])
            ->name('workbench.ui.component');
        Route::get('workbench/ui/{type}/{component}/{renderer}', [UiKitchenSinkController::class, 'show'])
            ->whereIn('type', ['controls', 'nodes'])
            ->whereIn('renderer', ['vue', 'html'])
            ->name('workbench.ui.show');
        Route::get('workbench/layout-slots', LayoutSlotsDemoController::class)
            ->name('workbench.layout-slots');
        Route::get('workbench/chips', ChipsController::class)
            ->name('workbench.chips');
    });
