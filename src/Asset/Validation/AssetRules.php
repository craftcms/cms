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
 */
final class AssetRules extends ElementRules
{
    public const string SCENARIO_MOVE = 'move';

    public const string SCENARIO_FILEOPS = 'fileOperations';

    public const string SCENARIO_INDEX = 'index';

    public const string SCENARIO_CREATE = 'create';

    public const string SCENARIO_REPLACE = 'replace';

    #[Override]
    public function scenarios(): array
    {
        return array_merge(parent::scenarios(), [
            self::SCENARIO_MOVE => null,
            self::SCENARIO_FILEOPS => null,
            self::SCENARIO_INDEX => [],
            self::SCENARIO_CREATE => null,
            self::SCENARIO_REPLACE => null,
        ]);
    }

    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules['title'] = [
            'nullable',
            Rule::when($this->inScenarios(Asset::SCENARIO_CREATE), [
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
            Rule::requiredIf($this->inScenarios(self::SCENARIO_CREATE, self::SCENARIO_MOVE, self::SCENARIO_FILEOPS)),
            Rule::when(! $this->inScenarios(self::SCENARIO_MOVE), [
                new AssetLocationRule($this->component),
            ]),
            Rule::when($this->inScenarios(self::SCENARIO_MOVE), [
                new AssetLocationRule($this->component, allowedExtensions: '*'),
            ]),
        ];

        $rules['tempFilePath'] = [
            'nullable',
            Rule::requiredIf($this->inScenarios(self::SCENARIO_CREATE, self::SCENARIO_REPLACE)),
        ];

        return $rules;
    }
}
