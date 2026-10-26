<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Support\Url;
use Override;

/**
 * The edit screen payload for an Eloquent-backed element.
 *
 * It opens the activity panel once the element exists. Like assets, these
 * elements have no full activity page.
 *
 * @since 6.0.0
 */
class EloquentElementEditViewModel extends ElementEditViewModel
{
    #[Override]
    public function activityTimelineUrl(): ?string
    {
        return $this->element->id
            ? Url::actionUrl('elements/activity')
            : null;
    }
}
