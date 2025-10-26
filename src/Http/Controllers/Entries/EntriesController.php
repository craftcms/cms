<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use craft\elements\Entry;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\RedirectResponse;

use function CraftCms\Cms\cp_redirect;

final class EntriesController
{
    public function index(): RedirectResponse
    {
        $firstPage = \Craft::$app->getElementSources()->getFirstPage(Entry::class);
        $slug = $firstPage ? Str::slug($firstPage) : 'entries';

        return cp_redirect("content/$slug");
    }
}
