<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Route;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Route\CurrentElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguredEntryController
{
    public function __invoke(#[CurrentElement] ElementInterface $entry, Request $request): JsonResponse
    {
        return new JsonResponse(['id' => $entry->id, 'path' => $request->path()]);
    }

    public function show(#[CurrentElement] Entry $entry, string $slug, Request $request): JsonResponse
    {
        return new JsonResponse(['id' => $entry->id, 'slug' => $slug, 'routeSlug' => $request->route('slug')]);
    }
}
