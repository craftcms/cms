<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cp\CommandPalette\Actions;
use CraftCms\Cms\Cp\CommandPalette\CommandPalette;
use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the control panel’s command palette.
 *
 * @since 6.0.0
 */
readonly class CommandPaletteController
{
    /** The most results a single provider returns for one query. */
    private const int RESULT_LIMIT = 20;

    public function __construct(
        private CommandPalette $commandPalette,
        private Actions $actions,
    ) {}

    /**
     * What the palette needs before anything is typed: the providers to ask
     * once something is, and the actions to list until then.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->craftUser();

        return new JsonResponse([
            'providers' => array_values(array_map(
                fn (SearchProvider $provider) => [
                    'handle' => $provider->getHandle(),
                    'label' => $provider->getLabel(),
                ],
                $this->commandPalette->getAvailableSearchProviders($user),
            )),
            'actions' => collect($this->actions->all($user))
                ->reject(fn (CommandPaletteItem $action) => $action->searchOnly)
                ->values()
                ->toArray(),
        ]);
    }

    public function search(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->craftUser();
        $searchProvider = $this->commandPalette->getSearchProvider($provider);

        abort_if($searchProvider === null || ! $searchProvider->isAvailable($user), 404);

        $results = [];

        foreach ($searchProvider->search($request->string('query')->trim()->value(), $user) as $item) {
            $results[] = $item;

            if (count($results) === self::RESULT_LIMIT) {
                break;
            }
        }

        return new JsonResponse([
            'results' => collect($results)->toArray(),
        ]);
    }
}
