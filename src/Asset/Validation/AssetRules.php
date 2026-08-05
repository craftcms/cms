<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\Validation\Rules\DisallowMb4;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<Asset>
 *
 * @property Asset $subject
 */
class AssetRules extends ElementRules
{
    /**
     * Validation scenario that should be used when the asset is only getting *moved*; not renamed.
     */
    public const string SCENARIO_MOVE = 'move';

    public const string SCENARIO_FILEOPS = 'fileOperations';

    public const string SCENARIO_INDEX = 'index';

    public const string SCENARIO_CREATE = 'create';

    public const string SCENARIO_REPLACE = 'replace';

    /** @return array<string, list<array<int, object|string>|Closure|object|string>> */
    #[Override]
    public function rules(): array
    {
        if ($this->inScenarios(self::SCENARIO_INDEX)) {
            return [];
        }

        $rules = parent::rules();

        $rules['title'] = [
            'nullable',
            Rule::when($this->inScenarios(self::SCENARIO_CREATE), [
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
            if (! $this->inScenarios(self::SCENARIO_LIVE)) {
                return false;
            }

            return $this->subject
                ->getFieldLayout()
                ?->getFirstVisibleElementByType(AltField::class, $this->subject)
                ->required ?? false;
        })];

        $rules['newLocation'] = [
            'nullable',
            Rule::requiredIf($this->inScenarios(self::SCENARIO_CREATE, self::SCENARIO_MOVE, self::SCENARIO_FILEOPS)),
            Rule::when(! $this->inScenarios(self::SCENARIO_MOVE), [
                new AssetLocationRule($this->subject),
            ]),
            Rule::when($this->inScenarios(self::SCENARIO_MOVE), [
                new AssetLocationRule($this->subject, allowedExtensions: '*'),
            ]),
        ];

        $rules['tempFilePath'] = [
            'nullable',
            Rule::requiredIf($this->inScenarios(self::SCENARIO_CREATE, self::SCENARIO_REPLACE)),
        ];

        return $rules;
    }
}
