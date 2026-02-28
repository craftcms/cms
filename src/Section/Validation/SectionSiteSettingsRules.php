<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Validation;

use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Validation\Rules\SingleSectionUriRule;
use CraftCms\Cms\Validation\Rules\SiteIdRule;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use CraftCms\Cms\Validation\Ruleset;

/** @extends Ruleset<\CraftCms\Cms\Section\Data\SectionSiteSettings> */
final class SectionSiteSettingsRules extends Ruleset
{
    #[Override]
    #[\Override]
    public function defineRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'siteId' => ['nullable', 'integer', new SiteIdRule],
            'template' => ['nullable', 'string', 'max:500'],
            'uriFormat' => array_merge(
                ['required_if:hasUrls,true', new UriFormatRule],
                $this->component->section?->type === SectionType::Single->value
                    ? [new SingleSectionUriRule]
                    : [],
            ),
            'hasUrls' => ['nullable', 'boolean'],
        ];
    }
}
