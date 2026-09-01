<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\EntryIndexViewModel;
use Inertia\Inertia;
use Inertia\Response;

readonly class ContentIndexController
{
    public function __invoke(ElementIndexRequest $request, string $page, ?string $sectionHandle = null): Response
    {
        return Inertia::render('content/Index', new EntryIndexViewModel(
            request: $request,
            page: $page,
            sectionHandle: $sectionHandle,
        ));
    }
}
