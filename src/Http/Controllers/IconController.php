<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Search;
use CraftCms\DependencyAwareCache\Dependency\FileDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

readonly class IconController
{
    public function svg(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => ['required', 'string'],
        ]);

        $icon = $request->string('icon')->toString();

        if (! preg_match('/^[\w\-]+$/', $icon)) {
            abort(400, "Invalid icon: $icon");
        }

        return new JsonResponse([
            'iconSvg' => Icons::svg($icon),
        ]);
    }

    public function pickerOptions(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string'],
            'freeOnly' => ['nullable', 'boolean'],
        ]);

        $search = $request->input('search');
        $freeOnly = $request->boolean('freeOnly', true);
        $noSearch = empty($search);

        // Holds data, not markup, so it shares nothing with the HTML lists
        // Craft 5 cached for its own picker.
        $cacheKey = sprintf('icon-picker-options-icons%s', $freeOnly ? ':free' : '');

        if ($noSearch) {
            if (DependencyCache::has($cacheKey)) {
                return new JsonResponse([
                    'icons' => DependencyCache::get($cacheKey),
                ]);
            }

            $searchTerms = null;
        } else {
            $searchTerms = explode(' ', Search::normalizeKeywords($search));
        }

        $indexPath = CmsAssets::resourcesPath('icons/index.php');
        $icons = require $indexPath;
        $output = [];
        $scores = [];

        foreach ($icons as $name => $icon) {
            $name = (string) $name;

            if ($freeOnly && $icon['pro']) {
                continue;
            }

            if ($searchTerms) {
                $score = $this->matchTerms($searchTerms, $icon['name']) * 5 + $this->matchTerms($searchTerms, $icon['terms']);
                if ($score === 0) {
                    continue;
                }
                $scores[] = $score;
            }

            $output[] = [
                'name' => $name,
                'svg' => (string) file_get_contents(Icons::resolveIconPath($name)),
            ];
        }

        if ($searchTerms) {
            array_multisort($scores, SORT_DESC, $output);
        }

        if ($noSearch) {
            DependencyCache::put(
                key: $cacheKey,
                value: $output,
                dependency: new FileDependency($indexPath),
            );
        }

        // The picker renders the options itself, from their names and SVGs.
        return new JsonResponse([
            'icons' => $output,
        ]);
    }

    /** @param list<string> $searchTerms */
    private function matchTerms(array $searchTerms, string $indexTerms): int
    {
        $score = 0;

        foreach ($searchTerms as $searchTerm) {
            // extra points for whole word matches
            if (str_contains($indexTerms, " $searchTerm ")) {
                $score += 10;
            } elseif (str_contains($indexTerms, " $searchTerm")) {
                $score += 1;
            } else {
                return 0;
            }
        }

        return $score;
    }
}
