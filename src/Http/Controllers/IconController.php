<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Html;
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

        return new JsonResponse([
            'iconSvg' => Icons::svg($request->string('icon')->toString()),
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

        $cacheKey = sprintf('icon-picker-options-list-html%s', $freeOnly ? ':free' : '');

        if ($noSearch) {
            if (DependencyCache::has($cacheKey)) {
                return new JsonResponse([
                    'listHtml' => DependencyCache::get($cacheKey),
                ]);
            }

            $searchTerms = null;
        } else {
            $searchTerms = explode(' ', Search::normalizeKeywords($search));
        }

        $indexPath = '@craftcms/resources/icons/index.php';
        $icons = require Aliases::get($indexPath);
        $output = [];
        $scores = [];

        foreach ($icons as $name => $icon) {
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

            $file = Aliases::get("@appicons/$name.svg");
            $output[] = Html::beginTag('li').
                Html::button(file_get_contents($file), [
                    'class' => 'icon-picker--icon',
                    'title' => $name,
                    'aria' => [
                        'label' => $name,
                    ],
                ])->encode(false).
                Html::endTag('li');
        }

        if ($searchTerms) {
            array_multisort($scores, SORT_DESC, $output);
        }

        $listHtml = implode('', $output);

        if ($noSearch) {
            DependencyCache::put(
                key: $cacheKey,
                value: $listHtml,
                dependency: new FileDependency($indexPath),
            );
        }

        return new JsonResponse([
            'listHtml' => $listHtml,
        ]);
    }

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
