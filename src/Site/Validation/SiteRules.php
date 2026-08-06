<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Validation;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\Validation\Rules\LanguageRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** @extends Ruleset<Site> */
class SiteRules extends Ruleset
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'language' => [
                'required',
                new LanguageRule(false, parseValue: true),
            ],
            'handle' => [
                'required',
                'string',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                Rule::when(
                    Cms::isInstalled(),
                    [new Unique(Table::SITES, 'handle')->ignore($this->subject->id)->withoutTrashed('dateDeleted')],
                ),
            ],
            'name' => new EnvValueRule([
                'required',
                'string',
                Rule::when(
                    Cms::isInstalled(),
                    [new Unique(Table::SITES, 'name')->ignore($this->subject->id)->withoutTrashed('dateDeleted')],
                ),
            ]),
        ];
    }
}
