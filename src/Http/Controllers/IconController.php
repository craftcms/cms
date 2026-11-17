<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Closure;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Cp\IconSets\IconSet;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Search;
use CraftCms\DependencyAwareCache\Dependency\FileDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
readonly class IconController
{
    public function svg(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => ['required', 'string'],
        ]);

        $icon = $request->string('icon')->toString();

        if (! preg_match('/^[\w\-]+$/', $icon) && ! IconSets::parse($icon)) {
            abort(400, "Invalid icon: $icon");
        }

        return new JsonResponse([
            'iconSvg' => Icons::svg($icon),
        ]);
    }

    public function pickerOptions(Request $request, IconSets $iconSets): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string'],
            'freeOnly' => ['nullable', 'boolean'],
            'sets' => ['nullable', function (string $attribute, mixed $value, Closure $fail) {
                if ($value !== '*' && ! is_array($value)) {
                    $fail('The sets field must be an array or “*”.');
                }
            }],
            'sets.*' => ['string'],
        ]);

        $search = $request->input('search');
        $freeOnly = $request->boolean('freeOnly', true);
        $searchTerms = empty($search) ? null : explode(' ', Search::normalizeKeywords($search));
        $setNames = $iconSets->getIconSetNames();
        $setHandles = $request->input('sets') ?? [IconSets::FONT_AWESOME];

        if ($setHandles === '*') {
            $setHandles = array_keys($setNames);
        } else {
            // Preserve the requested order
            $setHandles = array_values(array_unique(array_filter(
                $setHandles,
                fn (mixed $handle) => is_string($handle) && isset($setNames[$handle]),
            )));
        }

        $output = [];

        foreach ($setHandles as $handle) {
            if ($handle === IconSets::FONT_AWESOME) {
                $icons = array_map(fn (array $icon) => [
                    ...$icon,
                    'value' => $icon['name'],
                    'set' => IconSets::FONT_AWESOME,
                ], $this->fontAwesomeIcons($searchTerms, $freeOnly));
            } else {
                /** @var IconSet $iconSet */
                $iconSet = $iconSets->getIconSetByHandle($handle);
                $icons = $this->customIcons($iconSet, $searchTerms);
            }

            array_push($output, ...$icons);
        }

        // The picker renders the options itself, from their names and SVGs,
        // grouped under the sets’ names when there’s more than one.
        return new JsonResponse([
            'icons' => $output,
            'sets' => array_map(fn (string $handle) => [
                'handle' => $handle,
                'name' => $setNames[$handle],
            ], $setHandles),
        ]);
    }

    /**
     * @param  list<string>|null  $searchTerms
     * @return list<array{name: string, svg: string}>
     */
    private function fontAwesomeIcons(?array $searchTerms, bool $freeOnly): array
    {
        // Holds data, not markup, so it shares nothing with the HTML lists
        // Craft 5 cached for its own picker.
        $cacheKey = sprintf('icon-picker-options-icons%s', $freeOnly ? ':free' : '');

        if (! $searchTerms && DependencyCache::has($cacheKey)) {
            return DependencyCache::get($cacheKey);
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
        } else {
            DependencyCache::put(
                key: $cacheKey,
                value: $output,
                dependency: new FileDependency($indexPath),
            );
        }

        return $output;
    }

    /**
     * @param  list<string>|null  $searchTerms
     * @return list<array{name: string, svg: string, value: string, set: string}>
     */
    private function customIcons(IconSet $iconSet, ?array $searchTerms): array
    {
        $output = [];
        $scores = [];

        foreach ($iconSet->getIcons() as $name) {
            $score = 0;

            if ($searchTerms) {
                $nameTerms = sprintf(' %s ', Search::normalizeKeywords(str_replace(['-', '_'], ' ', $name)));
                $keywords = sprintf(' %s ', Search::normalizeKeywords(implode(' ', $iconSet->getKeywords($name))));
                $score = $this->matchTerms($searchTerms, $nameTerms) * 5 + $this->matchTerms($searchTerms, $keywords);

                if ($score === 0) {
                    continue;
                }
            }

            $value = "$iconSet->handle:$name";
            $svg = Icons::svg($value);

            if (! $svg) {
                continue;
            }

            $output[] = [
                'name' => $name,
                'svg' => $svg,
                'value' => $value,
                'set' => $iconSet->handle,
            ];
            $scores[] = $score;
        }

        if ($searchTerms) {
            array_multisort($scores, SORT_DESC, $output);
        }

        return $output;
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
