<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use craft\elements\Entry;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\RedirectResponse;

use function CraftCms\Cms\cp_redirect;

final readonly class EntriesIndexController
{
    public function __invoke(ElementSources $elementSources): RedirectResponse
    {
        $slug = Str::slug($elementSources->getFirstPage(Entry::class) ?? 'entries');

        return cp_redirect("content/$slug");
    }
}
