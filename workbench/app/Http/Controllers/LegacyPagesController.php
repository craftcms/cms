<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RequirementsChecker;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control panel pages that still draw their own document instead of rendering
 * inside the Inertia shell, each with enough sample data to show its layout.
 */
class LegacyPagesController
{
    /** @var array<string, string> */
    public const array PAGES = [
        'dbupdate' => 'Database update',
        'cantrun' => 'Can’t run Craft',
        'setup-2fa' => '2FA setup',
        'preview' => 'Standalone preview',
    ];

    public function __invoke(Request $request, string $page): Response|RedirectResponse
    {
        // These are whole documents, which Inertia would show in a modal.
        if ($request->inertia()) {
            return Inertia::location($request->fullUrl());
        }

        return match ($page) {
            'dbupdate' => response()->view('_special/dbupdate'),
            'cantrun' => response()->view('_special/cantrun', ['reqCheck' => $this->failingRequirements()]),
            'setup-2fa' => response()->view('_special/setup-2fa'),
            'preview' => $this->preview(),
            default => abort(404),
        };
    }

    private function failingRequirements(): RequirementsChecker
    {
        return new RequirementsChecker()->check([
            [
                'name' => 'PHP 8.4+',
                'mandatory' => true,
                'condition' => false,
                'memo' => 'PHP 8.4 or later is required.',
            ],
            [
                'name' => 'Intl extension',
                'mandatory' => true,
                'condition' => false,
                'memo' => 'The <a href="https://php.net/manual/en/book.intl.php">Intl extension</a> is required.',
            ],
        ]);
    }

    private function preview(): RedirectResponse
    {
        $entry = Entry::find()->section('*')->one();

        abort_if($entry === null, 404, 'Standalone preview needs at least one entry.');

        return redirect(Url::cpUrl("preview/{$entry->id}-{$entry->slug}"));
    }
}
