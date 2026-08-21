<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\ImportConfig;
use CraftCms\Cms\Support\Facades\ImportRun as ImportRunFacade;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

use function CraftCms\Cms\t;

class ImportRun extends Component implements CpEditable, Validatable
{
    public ?string $name = null;

    public ?string $handle = null;

    public ?string $description = null;

    public ?array $steps = null;

    public ?string $uid = null;

    /**
     * Normalizes an object/array config, JSON-decodes `steps` if it's a string, then delegates to the parent constructor.
     *
     * @param  object|array  $config  The import run config.
     */
    public function __construct(object|array $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        if (isset($config['steps']) && is_string($config['steps'])) {
            $config['steps'] = Json::decode($config['steps']);
        }

        parent::__construct($config);
    }

    /**
     * Defines validation rules for name/handle/description/steps (uniqueness of handle, file requiredness per step, batch size bounds).
     */
    #[\Override]
    public function getRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                function ($attribute, $value, Closure $fail, Validator $validator) {
                    $found = ImportRunFacade::getImportRunByHandle($value);
                    if ($found !== null && $found->uid !== $validator->getValue('uid')) {
                        $fail(t('{attribute} "{value}" has already been taken.', [
                            'attribute' => $attribute,
                            'value' => $value,
                        ]));
                    }
                },
            ],
            'description' => [
                'string',
                'nullable',
            ],
            'steps' => [
                'required',
            ],
            'steps.*.config' => [
                Rule::in(array_merge(ImportConfig::getEditableConfigs()->pluck('uid')->toArray(), ImportConfig::getNonEditableConfigs()->keys()->all())),
            ],
            'steps.*.file' => [
                function ($attribute, $value, Closure $fail, Validator $validator) {
                    $key = preg_match('/\d+/', $attribute, $matches) ? (int) $matches[0] : null;
                    $config = ImportConfig::getConfigByHandle($this->steps[$key]['config']) ?? ImportConfig::getConfigByUid($this->steps[$key]['config']);
                    if ($config && ! $config->isEditable()) {
                        // if the config is not editable (file-based),
                        // then the file is required and has to be valid
                        return BaseImporter::validateFile($value, $attribute, $fail, $validator, 'steps');
                    }

                    // if config is editable, clear out the file value, just in case
                    if ($config && $config->isEditable()) {
                        $this->steps[$key]['file'] = null;
                    }

                    return true;
                },
            ],
            'steps.*.batchSize' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000',
            ],
        ];
    }

    /**
     * Moves nested `steps.*` validation error messages up to a top-level `steps` key.
     */
    public function afterValidate(?Validator $validator = null): void
    {
        // move all the nested steps validation messages up top for now
        // we might want to change this if/once the editable table is rewritten
        if ($validator->errors()->has('steps.*')) {
            $nestedErrors = $validator->errors()->get('steps.*');
            foreach ($nestedErrors as $key => $bag) {
                $validator->errors()->add('steps', ...$bag);
                $validator->errors()->forget($key);
            }
        }
    }

    //    public function getMessages(): array
    //    {
    //        return [
    //            //'steps.*.file' => 'test234', // works
    //            //'steps.*.file.closure_validation_rule' => 'test567', // works
    //        ];
    //    }
    /**
     * Returns a plain array snapshot of the run's properties.
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'steps' => $this->steps,
            'uid' => $this->uid,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl(): ?string
    {
        if (! $this->handle || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return Url::cpUrl("import/runs/$this->uid");
    }
}
