<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\Imports as ImportsFacade;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;

/**
 * A named, handled import: an ordered list of steps, each of which pairs an importer type
 * with its own file, transformer, settings and mapping.
 */
class Import extends Component implements CpEditable, Validatable
{
    public ?string $name = null;

    public ?string $handle = null;

    public ?string $description = null;

    /**
     * @var array<int, array<string, mixed>>|null An ordered list of step arrays, each shaped
     *                                            `{uid, type, file, transformer, batchSize, settings}`.
     */
    public ?array $steps = null;

    public ?string $uid = null;

    /**
     * Whether this import is stored in the database, and so editable via the control panel.
     * File-based imports, declared through the `craft.import` config, are not.
     */
    public bool $editable = false;

    /**
     * Normalizes an object/array config and JSON-decodes `steps` if it's a string, then
     * delegates to the parent constructor.
     *
     * @param  object|array  $config  The import config.
     */
    public function __construct(object|array $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        $steps = [];
        if (isset($config['steps']) && is_string($config['steps'])) {
            $items = Json::decode($config['steps']);
            foreach ($items as $item) {
                $step = ImportsFacade::createImporter($item);
                $steps[] = $step;
            }
            $config['steps'] = array_filter($steps);
        }

        parent::__construct($config);
    }

    /**
     * Sets the name for the import.
     *
     * @param  string|null  $name  The name to set.
     */
    public function name(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the handle for the import.
     *
     * @param  string|null  $handle  The handle to be assigned.
     */
    public function handle(?string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the description for the import.
     *
     * @param  string|null  $description  The description to be assigned.
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the import's steps, assigning a UID to any step that doesn't have one yet.
     *
     * @param  array<array-key, array<string, mixed>>|null  $steps  The steps to set.
     */
    public function steps(?array $steps): self
    {
        if ($steps === null) {
            $this->steps = null;

            return $this;
        }

        $this->steps = self::normalizeSteps($steps);

        return $this;
    }

    /**
     * Normalize an array of steps (which could be an array or arrays) into an array of BaseImporter objects.
     */
    private static function normalizeSteps(array $steps): ?array
    {
        if (empty($steps)) {
            return null;
        }

        $items = [];
        foreach ($steps as $step) {
            if (is_array($step)) {
                $step = ImportsFacade::createImporter($step);
            }
            if (empty($step->uid)) {
                $step->uid = Str::uuid7()->toString();
            }

            $items[] = $step;
        }

        return $items;
    }

    /**
     * Serializes the import's steps into an array of arrays.'
     */
    public function serializeSteps(): ?array
    {
        if ($this->steps === null) {
            return null;
        }

        return array_map(fn (array|BaseImporter $importer) => is_array($importer) ? $importer : $importer->toArrayData(), $this->steps);
    }

    /**
     * Defines validation rules for the import itself. Each step is validated separately,
     * against its own importer's rules, in `afterValidate()`.
     */
    #[Override]
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
                // editable only: handles are unique among saved imports, and checking against
                // the file-based ones would re-enter the lookup that is validating them
                function ($attribute, $value, Closure $fail, Validator $validator) {
                    $found = ImportsFacade::getImportByHandle($value, true);
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
                'array',
                'min:1',
            ],
        ];
    }

    /**
     * Defines custom validation messages for the import.
     */
    #[Override]
    public function getMessages(): array
    {
        return [
            'steps.required' => t('An import needs at least one step.'),
            'steps.min' => t('An import needs at least one step.'),
        ];
    }

    /**
     * Validates each step against its own importer type's rules, folding any errors back into
     * the import's error bag under `steps.<step uid>.<attribute>`.
     */
    #[Override]
    public function afterValidate(?Validator $validator = null): void
    {
        if ($validator === null) {
            return;
        }

        foreach ($this->steps ?? [] as $i => $step) {
            $key = $step->uid ?? $i;

            foreach (self::stepErrors($step) as $attribute => $messages) {
                $validator->errors()->add("steps.$key.$attribute", ...$messages);
            }
        }
    }

    /**
     * Validates a single step's data against its importer type's rules. Used both when
     * validating the import as a whole, and by the step slideout to check a draft step
     * before it lets the step close.
     *
     * @param  array<string, mixed>  $step  The step to validate.
     * @return array<string, array<int, string>> Validation messages keyed by attribute.
     */
    public static function stepErrors(array|BaseImporter $step): array
    {
        if (is_array($step)) {
            $type = $step['type'] ?? null;
            $stepArray = $step;
        } else {
            $type = $step::class ?? null;
            $stepArray = $step->toArrayData();
        }

        $typeValidator = ValidatorFacade::make(['type' => $type], [
            'type' => ['required', 'string', Rule::in(ImportFacade::getAllImporterTypes())],
        ]);

        if ($typeValidator->fails()) {
            return ['type' => $typeValidator->errors()->get('type')];
        }

        $stepValidator = ValidatorFacade::make($stepArray, $type::getRules());

        return $stepValidator->fails() ? $stepValidator->errors()->messages() : [];
    }

    /**
     * Returns an importer instance per step, skipping any step whose importer can't be built.
     *
     * @return Collection<array-key, BaseImporter>
     */
    public function getImporters(): Collection
    {
        return collect($this->steps ?? [])
            ->map(fn (array $step) => ImportsFacade::createImporter($step))
            ->filter()
            ->values();
    }

    /**
     * Returns a plain array snapshot of the import's properties.
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'steps' => $this->steps,
            'uid' => $this->uid,
        ];
    }

    /**
     * Determines whether the import is stored in the database, and therefore editable via the
     * control panel. File-based imports are not.
     */
    public function isEditable(): bool
    {
        return $this->editable;
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl(): ?string
    {
        if (! $this->handle || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return Url::cpUrl("import/$this->handle");
    }
}
