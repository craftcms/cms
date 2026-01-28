<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Shared\Rules\DisallowMb4;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<Asset>
 *
 * @property Asset $component
 */
final class AssetRules extends ElementRules
{
    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules['title'] = [
            'nullable',
            Rule::when($this->component->inScenarios(Asset::SCENARIO_CREATE), [
                'string',
                'max:255',
                new DisallowMb4,
            ]),
        ];

        $rules['volumeId'] = ['nullable', 'integer'];
        $rules['folderId'] = ['nullable', 'integer'];
        $rules['width'] = ['nullable', 'integer'];
        $rules['height'] = ['nullable', 'integer'];
        $rules['size'] = ['nullable', 'integer'];
        $rules['dateModified'] = ['nullable', 'date'];
        $rules['filename'] = ['required'];
        $rules['kind'] = ['required', 'string', 'max:50'];
        $rules['alt'] = ['nullable'];

        $rules['newLocation'] = [
            'nullable',
            Rule::requiredIf($this->component->inScenarios(Asset::SCENARIO_CREATE, Asset::SCENARIO_MOVE, Asset::SCENARIO_FILEOPS)),
            Rule::when(! $this->component->inScenarios(Asset::SCENARIO_MOVE), [
                new AssetLocationRule($this->component),
            ]),
            Rule::when($this->component->inScenarios(Asset::SCENARIO_MOVE), [
                new AssetLocationRule($this->component, allowedExtensions: '*'),
            ]),
        ];

        $rules['tempFilePath'] = [
            'nullable',
            Rule::requiredIf($this->component->inScenarios(Asset::SCENARIO_CREATE, Asset::SCENARIO_REPLACE)),
        ];

        return $rules;
    }
}
