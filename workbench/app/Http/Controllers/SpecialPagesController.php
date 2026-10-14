<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RequirementsChecker;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control panel pages that still draw their own document instead of rendering
 * inside the Inertia shell, grouped by the layout they extend, each with
 * enough sample data to show its layout.
 */
class SpecialPagesController
{
    /** @var array<string, array<string, string>> */
    public const array GROUPS = [
        'Messages' => [
            'dbupdate' => 'Database update',
            'cantrun' => 'Can’t run Craft',
            'set-password' => 'Set password',
            'verify-email' => 'Verify email',
        ],
        'Auth pages' => [
            'setup-2fa' => '2FA setup',
        ],
        'Preview' => [
            'preview' => 'Standalone preview',
        ],
    ];

    /** @return list<string> */
    public static function pages(): array
    {
        return array_keys(array_merge(...array_values(self::GROUPS)));
    }

    public function __invoke(Request $request, string $page): Response|RedirectResponse
    {
        // These are whole documents, which Inertia would show in a modal.
        if ($request->inertia()) {
            return Inertia::location($request->fullUrl());
        }

        return match ($page) {
            'dbupdate' => response()->view('_special/dbupdate'),
            'cantrun' => response()->view('_special/cantrun', ['reqCheck' => $this->failingRequirements()]),
            'set-password' => response()->view('set-password', [
                'uid' => Str::uuid()->toString(),
                'code' => 'workbench',
                'newUser' => true,
                'passwordRules' => 'minlength: 8;',
            ]),
            'verify-email' => response()->view('verify-email', [
                'uid' => Str::uuid()->toString(),
                'code' => 'workbench',
            ]),
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
