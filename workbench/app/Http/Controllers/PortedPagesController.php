<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Sleep;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages that used to be standalone Twig documents, in each state they can be
 * in. The updater scenarios run against fake steps, so nothing is installed.
 */
class PortedPagesController
{
    /** @var array<string, string> */
    public const array PAGES = [
        'updater-progress' => 'Updater: in progress',
        'updater-error' => 'Updater: error',
        'updater-finished' => 'Updater: finished',
        'email-taken' => 'Email taken',
        'licensing-issue' => 'Licensing: one issue',
        'licensing-issues' => 'Licensing: several issues',
    ];

    /** @var list<string> */
    private const array STEPS = [
        'Checking for updates…',
        'Updating Composer dependencies (this may take a minute)…',
        'Installing the plugin…',
    ];

    public function __invoke(string $page): Response|InertiaResponse
    {
        return match ($page) {
            'updater-progress' => $this->updater([
                'status' => self::STEPS[0],
                'nextUrl' => $this->stepUrl(1),
            ]),
            'updater-error' => $this->updater($this->errorState()),
            'updater-finished' => $this->updater([
                'status' => 'The plugin was installed successfully.',
                'finished' => true,
            ]),
            'email-taken' => Inertia::render('auth/EmailTaken', [
                'email' => 'jane@example.com',
            ]),
            'licensing-issue' => $this->licensing(['Craft Pro is installed, but no license has been purchased.'], 21),
            'licensing-issues' => $this->licensing([
                'Craft Pro is installed, but no license has been purchased.',
                'Commerce Pro is installed, but no license has been purchased.',
                'The license for SEOmatic is for a different domain.',
            ], 5),
            default => abort(404),
        };
    }

    /** Stands in for an updater action: a short wait, then the next state. */
    public function updaterStep(int $step): JsonResponse
    {
        Sleep::sleep(1);

        if (! isset(self::STEPS[$step])) {
            return new JsonResponse($this->state([
                'status' => 'The plugin was installed successfully.',
                'finished' => true,
            ]));
        }

        return new JsonResponse($this->state([
            'status' => self::STEPS[$step],
            'nextUrl' => $this->stepUrl($step + 1),
        ]));
    }

    /** @param array<string, mixed> $state */
    private function updater(array $state): InertiaResponse
    {
        return Inertia::render('updater/Index', [
            'title' => 'Plugin Installer',
            'initialState' => $this->state($state),
            'returnUrl' => Url::cpUrl('workbench/ported-pages/updater-finished'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function state(array $state): array
    {
        return $state + [
            'data' => 'workbench',
            'finishUrl' => $this->stepUrl(count(self::STEPS)),
            'returnUrl' => Url::cpUrl('workbench/ported-pages/updater-finished'),
        ];
    }

    /** @return array<string, mixed> */
    private function errorState(): array
    {
        return [
            'error' => 'Composer was unable to install the updates.',
            'errorDetails' => "Error details:\n\nYour requirements could not be resolved to an installable set of packages.\n\n  Problem 1\n    - Root composer.json requires `craftcms/commerce ^6.0` -> satisfiable by craftcms/commerce[6.0.0].\n    - craftcms/commerce 6.0.0 requires `php ^8.5` -> your php version (8.4.1) does not satisfy that requirement.",
            'options' => [
                ['label' => 'Try again', 'nextUrl' => $this->stepUrl(0), 'status' => 'Trying again…'],
                ['label' => 'Troubleshoot', 'url' => 'https://craftcms.com/knowledge-base/failed-updates'],
                ['label' => 'Send for help', 'email' => 'support@craftcms.com'],
            ],
        ];
    }

    private function stepUrl(int $step): string
    {
        return Url::cpUrl("workbench/ported-pages/updater-step/{$step}");
    }

    /** @param list<string> $issues */
    private function licensing(array $issues, int $duration): Response
    {
        return Inertia::render('licensing/Issues', [
            'issues' => $issues,
            'hash' => 'workbench',
            'cartUrl' => 'https://console.craftcms.com/cart',
            'duration' => $duration,
        ])->toResponse(request())->setStatusCode(402);
    }
}
