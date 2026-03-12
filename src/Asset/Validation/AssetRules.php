<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\assets\AltField;
use CraftCms\Cms\Validation\Rules\DisallowMb4;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<Asset>
 *
 * @property Asset $component
 */
class AssetRules extends ElementRules
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
        $rules['newFilename'] = ['nullable'];
        $rules['kind'] = ['required', 'string', 'max:50'];
        $rules['alt'] = ['nullable', Rule::requiredIf(function () {
            if (! $this->component->inScenarios(Asset::SCENARIO_LIVE)) {
                return false;
            }

            return $this->component
                ->getFieldLayout()
                ?->getFirstVisibleElementByType(AltField::class, $this->component)
                ->required ?? false;
        })];

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
