<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\FormKitchenSinkController;

Route::middleware(['craft', 'craft.cp', 'auth', 'can:accessCp'])
    ->prefix('{cpTrigger}/{actionTrigger}')
    ->get('workbench/text-expander-options', [FormKitchenSinkController::class, 'textExpanderOptions']);

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
    });
