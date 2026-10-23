<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Http\RedirectResponse;

use function CraftCms\Cms\cp_redirect;

/**
 * @since 6.0.0
 */
readonly class EntriesIndexController
{
    public function __invoke(ElementSources $elementSources, ?string $sectionHandle = null): RedirectResponse
    {
        $slug = ElementSources::pageSlug($elementSources->getFirstPage(Entry::class) ?? 'entries');

        if ($sectionHandle === 'singles') {
            return cp_redirect("content/$slug/singles");
        }

        if ($sectionHandle !== null) {
            $redirect = Sections::getSectionByHandle($sectionHandle)?->getCpIndexUri();

            if ($redirect) {
                return cp_redirect($redirect);
            }
        }

        return cp_redirect("content/$slug");
    }
}
