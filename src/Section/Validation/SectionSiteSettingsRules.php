<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Validation;

use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Validation\Rules\SingleSectionUriRule;
use CraftCms\Cms\Validation\Rules\SiteIdRule;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use CraftCms\Cms\Validation\Ruleset;
use Override;
use Throwable;

/** @extends Ruleset<\CraftCms\Cms\Section\Data\SectionSiteSettings> */
final class SectionSiteSettingsRules extends Ruleset
{
    #[Override]
    public function defineRules(): array
    {
        $section = null;
        try {
            $section = $this->component->getSection();
        } catch (Throwable) {
        }

        return [
            'id' => ['nullable', 'integer'],
            'siteId' => ['nullable', 'integer', new SiteIdRule],
            'template' => ['nullable', 'string', 'max:500'],
            'uriFormat' => array_merge(
                ['required_if:hasUrls,true', new UriFormatRule],
                $section?->type === SectionType::Single
                    ? [new SingleSectionUriRule($section)]
                    : [],
            ),
            'hasUrls' => ['nullable', 'boolean'],
        ];
    }
}
