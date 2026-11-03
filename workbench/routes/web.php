<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\ChipsController;
use Workbench\App\Http\Controllers\ErrorPagesController;
use Workbench\App\Http\Controllers\LayoutSlotsDemoController;
use Workbench\App\Http\Controllers\PortedPagesController;
use Workbench\App\Http\Controllers\SpecialPagesController;
use Workbench\App\Http\Controllers\UiKitchenSinkController;

use function CraftCms\Cms\cp_url;

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
        // Section landing URLs, so a section and its first page get nav ids of their own.
        Route::get('workbench/special-pages', fn () => redirect(cp_url('workbench/special-pages/dbupdate')));
        Route::get('workbench/error-pages', fn () => redirect(cp_url('workbench/error-pages/404')));
        Route::get('workbench/ported-pages', fn () => redirect(cp_url('workbench/ported-pages/updater-progress')));
        Route::get('workbench/special-pages/{page}', SpecialPagesController::class)
            ->whereIn('page', SpecialPagesController::pages())
            ->name('workbench.special-pages');
        Route::get('workbench/error-pages/{page}', ErrorPagesController::class)
            ->whereIn('page', array_keys(ErrorPagesController::PAGES))
            ->name('workbench.error-pages');
        Route::get('workbench/ported-pages/{page}', PortedPagesController::class)
            ->whereIn('page', array_keys(PortedPagesController::PAGES))
            ->name('workbench.ported-pages');
        Route::post('workbench/ported-pages/updater-step/{step}', [PortedPagesController::class, 'updaterStep'])
            ->whereNumber('step');
    });
