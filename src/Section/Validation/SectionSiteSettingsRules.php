<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Validation;

use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Validation\Rules\SingleSectionUriRule;
use CraftCms\Cms\Validation\Rules\SiteIdRule;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/** @extends Ruleset<SectionSiteSettings> */
class SectionSiteSettingsRules extends Ruleset
{
    /** @return array<string, array<int, string|ValidationRule>> */
    public function rules(): array
    {
        $section = null;
        try {
            $section = $this->subject->getSection();
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
