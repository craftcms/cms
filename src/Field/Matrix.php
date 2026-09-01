<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
use CraftCms\Cms\Field\Exceptions\InvalidFieldException;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\GroupedEntryTypeManager;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Matrix as MatrixControl;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Table as TableControl;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentInterface;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry as EntryResolver;
use CraftCms\Cms\Gql\Types\Generators\EntryType as EntryTypeGenerator;
use CraftCms\Cms\Gql\Types\Input\Matrix as MatrixInputType;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\MatrixAsset;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;
use Override;
use RuntimeException;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Matrix field type
 *
 * @phpstan-import-type ArgumentConfig from \GraphQL\Type\Definition\Argument
 * @phpstan-import-type InputObjectFieldConfig from \GraphQL\Type\Definition\InputObjectField
 *
 * @phpstan-type SerializedEntryData array{type?:string,title?:string|null,slug?:string|null,uid?:string|null,enabled?:bool|int|string,collapsed?:bool|int|string,fresh?:bool|int|string,fields?:array<string,mixed>}
 * @phpstan-type SerializedEntries array<int|string,SerializedEntryData>
 */
class Matrix extends Field implements EagerLoadingFieldInterface, ElementContainerFieldInterface, GqlInlineFragmentFieldInterface, MergeableFieldInterface
{
    public const string VIEW_MODE_CARDS = 'cards';

    public const string VIEW_MODE_CARDS_GRID = 'cards-grid';

    public const string VIEW_MODE_BLOCKS = 'blocks';

    public const string VIEW_MODE_INDEX = 'index';

    #[Override]
    public static function displayName(): string
    {
        return t('Matrix');
    }

    #[Override]
    public static function icon(): string
    {
        return 'binary';
    }

    #[Override]
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            TranslationMethod::Site,
        ];
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EntryQuery::class, ElementCollection::class, Entry::class);
    }

    #[Override]
    public static function dbType(): array|string|null
    {
        return null;
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        /** @var self $field */
        $field = reset($instances);
        $ns = $field->handle.'_'.Str::random(5);

        $exists = DB::table(Table::ENTRIES, "entries_$ns")
            ->join(new Alias(Table::ELEMENTS, "elements_$ns"), "elements_$ns.id", '=', "entries_$ns.id")
            ->join(new Alias(Table::ELEMENTS_OWNERS, "elements_owners_$ns"), "elements_owners_$ns.elementId", '=', "elements_$ns.id")
            ->where("entries_$ns.fieldId", $field->id)
            ->where("elements_$ns.enabled", true)
            ->whereNull("elements_$ns.dateDeleted")
            ->whereColumn("elements_owners_$ns.ownerId", 'elements.id');

        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':empty:') {
            return $query->whereNotExists($exists);
        }

        if ($value !== ':notempty:') {
            $ids = $value;
            if (! is_array($ids)) {
                $ids = is_string($ids) ? str($ids)->explode(',')->all() : [$ids];
            }

            $ids = array_map(fn ($id) => $id instanceof Entry ? $id->id : (int) $id, $ids);

            $exists->whereIn("entries_$ns.id", $ids);
        }

        return $query->whereExists($exists);
    }

    /**
     * Returns the “Default Table Columns” options for the given entry types.
     *
     * @param  EntryType[]  $entryTypes
     * @return list<array{label:string,value:string}>
     */
    public static function defaultTableColumnOptions(array $entryTypes): array
    {
        $fieldLayouts = array_map(fn (EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes);
        $tableColumns = array_merge(
            ElementSources::getAvailableTableAttributes(Entry::class)->all(),
            ElementSources::getTableAttributesForFieldLayouts(collect($fieldLayouts))->all(),
        );

        $options = [];
        foreach ($tableColumns as $attribute => $column) {
            $options[] = ['label' => $column['label'], 'value' => $attribute];
        }

        return $options;
    }

    public ?int $minEntries = null;

    public ?int $maxEntries = null;

    public bool $enableVersioning = false;

    /**
     * @var string The view mode
     *
     * @phpstan-var self::VIEW_MODE_*
     */
    public string $viewMode = self::VIEW_MODE_CARDS;

    /**
     * @var bool Include table view in element indexes
     */
    public bool $includeTableView = false;

    /**
     * @var string[] The default table columns to show in table view
     */
    public array $defaultTableColumns = [];

    /**
     * @var string The default view mode that should be used
     *             if the field's view mode is set to element index and has "Include Table View" turned on.
     */
    public string $defaultIndexViewMode = 'cards';

    /**
     * @var int|null The total entries to display per page within element indexes
     */
    public ?int $pageSize = null;

    /**
     * @var string|null The “New entry” button label.
     */
    public ?string $createButtonLabel = null;

    /**
     * @var PropagationMethod Propagation method
     *
     * This will be set to one of the following:
     *
     * - [[PropagationMethod::None]] – Only save entries in the site they were created in
     * - [[PropagationMethod::SiteGroup]] – Save entries to other sites in the same site group
     * - [[PropagationMethod::Language]] – Save entries to other sites with the same language
     * - [[PropagationMethod::Custom]] – Save entries to other sites based on a custom [[$propagationKeyFormat|propagation key format]]
     * - [[PropagationMethod::All]] – Save entries to all sites supported by the owner element
     */
    public PropagationMethod $propagationMethod = PropagationMethod::All;

    /**
     * @var string|null The field’s propagation key format, if [[propagationMethod]] is `custom`
     */
    public ?string $propagationKeyFormat = null;

    /**
     * @var array<string,array{uriFormat?:string|null,template?:string|null,errors?:array<string,list<string>>}> Site settings
     */
    public array $siteSettings = [];

    /**
     * @var EntryType[] The field’s available entry types
     *
     * @see getEntryTypes()
     * @see setEntryTypes()
     */
    private array $_entryTypes = [];

    /**
     * @see entryManager()
     */
    private NestedElementManager $_entryManager;

    public function __construct($config = [])
    {
        // Config normalization
        unset($config['contentTable']);

        if (array_key_exists('localizeBlocks', $config)) {
            $config['propagationMethod'] = $config['localizeBlocks'] ? 'none' : 'all';
            unset($config['localizeBlocks']);
        }

        if (isset($config['entryTypes']) && $config['entryTypes'] === '') {
            $config['entryTypes'] = [];
        }

        if (array_key_exists('minBlocks', $config)) {
            $config['minEntries'] = Arr::pull($config, 'minBlocks');
        }
        if (array_key_exists('maxBlocks', $config)) {
            $config['maxEntries'] = Arr::pull($config, 'maxBlocks');
        }
        if (isset($config['siteSettings']) && is_array($config['siteSettings'])) {
            foreach ($config['siteSettings'] as &$siteSettings) {
                if (is_array($siteSettings)) {
                    unset($siteSettings['heading']);
                }
            }
            unset($siteSettings);
        }

        parent::__construct($config);

        foreach ($this->siteSettings as &$siteSettings) {
            if (($siteSettings['uriFormat'] ?? null) === '') {
                unset($siteSettings['uriFormat']);
            }
            if (($siteSettings['template'] ?? null) === '') {
                unset($siteSettings['template']);
            }
        }

        if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
            $this->includeTableView = false;
            $this->pageSize = null;
        }

        if (! $this->includeTableView) {
            $this->defaultTableColumns = [];
        }

        if ($this->minEntries === 0) {
            $this->minEntries = null;
        }

        if ($this->maxEntries === 0) {
            $this->maxEntries = null;
        }
    }

    #[Override]
    public function settingsAttributes(): array
    {
        return Arr::except(parent::settingsAttributes(), 'localizeEntries');
    }

    #[Override]
    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['entryTypes'] = array_map(
            fn (EntryType $entryType) => $entryType->getUsageConfig(),
            $this->_entryTypes,
        );

        return $settings;
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'entryTypes' => ['array', 'min:1'],
            'siteSettings' => ['array'],
            'siteSettings.*.uriFormat' => ['nullable', new UriFormatRule],
            'siteSettings.*.template' => ['nullable', 'string', 'max:500'],
            'minEntries' => ['nullable', 'integer', 'min:0'],
            'maxEntries' => ['nullable', 'integer', 'min:0'],
            'viewMode' => Rule::in([
                self::VIEW_MODE_CARDS,
                self::VIEW_MODE_CARDS_GRID,
                self::VIEW_MODE_INDEX,
                self::VIEW_MODE_BLOCKS,
            ]),
        ]);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $objectTemplateTip = SelectOptions::getObjectTemplateTip();
        $ownerTemplateTriggers = SelectOptions::getObjectTemplateTextExpanderTriggers();
        $entryTemplateTriggers = SelectOptions::getObjectTemplateTextExpanderTriggers(
            Entry::class,
            array_map(fn (EntryType $entryType) => $entryType->getFieldLayout(), $this->_entryTypes),
        );
        $form = Form::make([
            FormField::make(t('Entry Types'))
                ->instructions(t('Choose the types of entries that can be created in this field.'))
                ->control(GroupedEntryTypeManager::make('entryTypes')
                    ->value(array_map(fn (EntryType $type): array => $type->getUsageConfig(), $this->_entryTypes))),
        ]);

        if (Sites::isMultiSite()) {
            $form->add(
                FormField::make(t('Propagation Method'))
                    ->instructions(t('Which sites should entries be saved to?'))
                    ->control(Choice::make('propagationMethod')->options([
                        ['label' => t('Only save entries to the site they were created in'), 'value' => PropagationMethod::None->value],
                        ['label' => t('Save entries to other sites in the same site group'), 'value' => PropagationMethod::SiteGroup->value],
                        ['label' => t('Save entries to other sites with the same language'), 'value' => PropagationMethod::Language->value],
                        ['label' => t('Save entries to all sites the owner element is saved in'), 'value' => PropagationMethod::All->value],
                        ['label' => t('Custom…'), 'value' => PropagationMethod::Custom->value],
                    ])->value($this->propagationMethod->value)),
                FormField::make(t('Propagation Key Format'))
                    ->instructions(t('Template that defines the field’s custom “propagation key” format. Entries will be saved to all sites that produce the same key.'))
                    ->control(Text::make('propagationKeyFormat')
                        ->monospace()
                        ->textExpanderTriggers($ownerTemplateTriggers)
                        ->value($this->propagationKeyFormat))
                    ->tip($objectTemplateTip),
            );
        }

        $siteSettings = [];
        foreach (Sites::getAllSites() as $site) {
            $siteSettings[$site->uid] = [
                'heading' => t($site->getName(), category: 'site'),
                'uriFormat' => $this->siteSettings[$site->uid]['uriFormat'] ?? '',
                ...(! config('craft.general.headlessMode') ? [
                    'template' => $this->siteSettings[$site->uid]['template'] ?? '',
                ] : []),
            ];
        }
        $siteColumns = [
            'heading' => ['heading' => t('Site'), 'type' => 'heading'],
            'uriFormat' => [
                'heading' => t('Entry URI Format'),
                'type' => 'singleline',
                'placeholder' => t('Leave blank if entries don’t have URLs'),
                'code' => true,
                'info' => $objectTemplateTip,
                'textExpanderTriggers' => $entryTemplateTriggers,
            ],
        ];
        if (! config('craft.general.headlessMode')) {
            $siteColumns['template'] = ['heading' => t('Template'), 'type' => 'singleline', 'code' => true];
        }

        $indexViewModes = array_values(array_map(fn (array $viewMode): array => [
            'label' => $viewMode['title'],
            'value' => $viewMode['mode'],
        ], array_filter(Entry::indexViewModes(), fn (array $viewMode): bool => ! ($viewMode['structuresOnly'] ?? false))));

        return $form->add(
            FormField::make(t('Site Settings'))
                ->instructions(t('Choose the site-specific settings for nested entries.'))
                ->control(TableControl::make('siteSettings')->columns($siteColumns)->keyed()->value($siteSettings)),
            FormField::make(t('Min {type}', ['type' => t('Entries')]))
                ->instructions(t('The minimum number of {type} the field is allowed to have.', ['type' => t('entries')]))
                ->control(Number::make('minEntries')->min(0)->value($this->minEntries)),
            FormField::make(t('Max {type}', ['type' => t('Entries')]))
                ->instructions(t('The maximum number of {type} the field is allowed to have.', ['type' => t('entries')]))
                ->control(Number::make('maxEntries')->min(0)->value($this->maxEntries)),
            FormField::make(t('Enable versioning for entries in this field'))
                ->control(Lightswitch::make('enableVersioning')->value($this->enableVersioning)),
            FormField::make(t('View Mode'))
                ->instructions(t('Choose how nested {type} should be presented to authors.', ['type' => t('entries')]))
                ->control(Choice::make('viewMode')
                    ->presentation(ChoicePresentation::Radios)
                    ->options([
                        ['label' => t('Cards'), 'value' => self::VIEW_MODE_CARDS],
                        ['label' => t('Card grid'), 'value' => self::VIEW_MODE_CARDS_GRID],
                        ['label' => t('Blocks'), 'value' => self::VIEW_MODE_BLOCKS],
                        ['label' => t('Index'), 'value' => self::VIEW_MODE_INDEX],
                    ])
                    ->value($this->viewMode)),
            FormField::make(t('Include Table View'))
                ->instructions(t('Whether the element index should allow viewing nested {type} in a table.', ['type' => t('entries')]))
                ->control(Lightswitch::make('includeTableView')->value($this->includeTableView)),
            FormField::make(t('Default Table Columns'))
                ->instructions(t('Choose which table columns should be visible by default.'))
                ->control(Choice::make('defaultTableColumns')
                    ->multiple()
                    ->options(self::defaultTableColumnOptions($this->_entryTypes))
                    ->value($this->defaultTableColumns)),
            FormField::make(t('Default View Mode'))
                ->control(Choice::make('defaultIndexViewMode')->options($indexViewModes)->value($this->defaultIndexViewMode)),
            FormField::make(t('{type} Per Page', ['type' => t('Entries')]))
                ->instructions(t('The total number of {type} to display per page within the element index.', ['type' => t('entries')]))
                ->control(Choice::make('pageSize')->options(array_map(fn (int $size): array => [
                    'label' => (string) $size,
                    'value' => $size,
                ], [10, 20, 50, 100]))->value($this->pageSize ?? 50)),
            FormField::make(t('“New” Button Label'))
                ->instructions(t('The text label for the entry creation button.'))
                ->control(Text::make('createButtonLabel')->placeholder($this->defaultCreateButtonLabel())->value($this->createButtonLabel)),
        );
    }

    private function entryManager(): NestedElementManager
    {
        if (! isset($this->_entryManager)) {
            $this->_entryManager = new NestedElementManager(
                Entry::class,
                fn (ElementInterface $owner) => $this->createEntryQuery($owner),
                [
                    'field' => $this,
                    'criteria' => [
                        'fieldId' => $this->id,
                    ],
                    'propagationMethod' => $this->propagationMethod,
                    'propagationKeyFormat' => $this->propagationKeyFormat,
                ],
            );

            Event::listen(function (NestedElementsSaved $event) {
                if ($event->manager !== $this->_entryManager) {
                    return;
                }

                $this->afterSaveEntries($event);
            });
        }

        return $this->_entryManager;
    }

    /**
     * Returns the available entry types.
     *
     * @return EntryType[]
     */
    public function getEntryTypes(): array
    {
        return $this->_entryTypes;
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $entryTypes = collect($this->getEntryTypes())
            ->mapWithKeys(fn (EntryType $type): array => [$type->handle => $type->name])
            ->all() ?: ['entry' => Entry::displayName()];
        $entries = array_values(match (true) {
            $context->value instanceof ElementCollection => $context->value->all(),
            $context->value instanceof EntryQuery => $context->value->all(),
            default => [],
        });
        $values = $forms = $sortOrder = [];
        $identities = ElementHelper::nestedElementIdentities($entries);

        foreach ($entries as $index => $entry) {
            if (! $entry instanceof Entry) {
                throw new LogicException('Matrix Controls require Entry values.');
            }

            $uid = $identities[$index];
            $values[$uid] = ['type' => $entry->getType()->handle];
            $forms[$uid] = app(FieldLayoutCompiler::class)->form(
                $entry->getFieldLayout(),
                $entry,
                new FormContext,
            );
            $sortOrder[] = $uid;
        }

        return MatrixControl::make($context->path)
            ->entryTypes($entryTypes)
            ->forms($forms)
            ->minEntries($this->minEntries)
            ->maxEntries($this->maxEntries)
            ->value(['entries' => $values, 'sortOrder' => $sortOrder]);
    }

    /**
     * Returns the available entry types for the given owner element.
     *
     * @param  Entry[]  $value
     * @return EntryType[]
     */
    public function getEntryTypesForField(array $value, ?ElementInterface $element): array
    {
        $entryTypes = $this->_entryTypes;

        event($event = new EntryTypesForFieldResolving(
            field: $this,
            entryTypes: $entryTypes,
            element: $element,
            value: $value,
        ));

        if (empty($event->entryTypes)) {
            throw new RuntimeException('At least one entry type is required.');
        }

        return array_values($event->entryTypes);
    }

    /**
     * Sets the available entry types.
     *
     * @param  array<EntryType|int|string|array{id?:int,uid?:string,name?:string,handle?:string}>  $entryTypes  The entry types
     */
    public function setEntryTypes(array $entryTypes): void
    {
        $entryTypesService = app(EntryTypes::class);
        $this->_entryTypes = array_values(array_filter(array_map(
            $entryTypesService->getEntryType(...),
            $entryTypes,
        )));
    }

    public function getFieldLayoutProviders(): array
    {
        return $this->_entryTypes;
    }

    public function getUriFormatForElement(NestedElementInterface $element): ?string
    {
        $site = $element->getSite();

        return $this->siteSettings[$site->uid]['uriFormat'] ?? null;
    }

    public function getRouteForElement(NestedElementInterface $element): mixed
    {
        $site = $element->getSite();

        return [
            'templates/render', [
                'template' => $this->siteSettings[$site->uid]['template'] ?? '',
                'variables' => [
                    'entry' => $element,
                ],
            ],
        ];
    }

    /** @return list<int> */
    public function getSupportedSitesForElement(NestedElementInterface $element): array
    {
        try {
            $owner = $element->getOwner();
        } catch (RuntimeException) {
            $owner = $element->duplicateOf;
        }

        if (! $owner) {
            return [Sites::getPrimarySite()->id];
        }

        return $this->entryManager()->getSupportedSiteIds($owner);
    }

    public function canViewElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        return $owner && $user->can('view', $owner);
    }

    public function canSaveElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $owner) {
            return false;
        }

        if ($user->can('save', $owner)) {
            return true;
        }

        // Check all the owners. Maybe the user can save one of the other ones?
        if (! $owner->getIsRevision()) {
            foreach ($element->getOwners(['revisions' => false]) as $o) {
                if ($o->id !== $owner->id && $user->can('save', $o)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canDuplicateElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $owner || ! $user->can('save', $owner)) {
            return false;
        }

        // Make sure we aren't hitting the Max Entries limit
        return ! $this->maxEntriesReached($owner);
    }

    public function canDeleteElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $owner || ! $user->can('save', $element->getOwner())) {
            return false;
        }

        return true;
    }

    public function canDeleteElementForSite(NestedElementInterface $element, User $user): bool
    {
        return false;
    }

    private function maxEntriesReached(ElementInterface $owner): bool
    {
        return
            $this->maxEntries &&
            $this->maxEntries <= $this->totalEntries($owner);
    }

    private function totalEntries(ElementInterface $owner): int
    {
        /** @var EntryQuery<Entry>|ElementCollection<int,Entry> $value */
        $value = $owner->getFieldValue($this->handle);

        if ($value instanceof EntryQuery) {
            return (clone $value)
                ->status(null)
                ->siteId($owner->siteId)
                ->getCountForPagination();
        }

        return $value->count();
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = $this->createEntryQuery($element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        // An empty POST value means every entry was removed. It arrives as
        // null rather than '' because of Laravel's ConvertEmptyStringsToNull
        // middleware — and delta ensures the value is only applied from the
        // request when the field was actually modified.
        if ($value === '' || ($fromRequest && $value === null)) {
            $query->setResultOverride([]);
        } elseif ($value === '*') {
            // preload the nested entries so NestedElementManager::saveNestedElements() doesn't resave them all
            $query->drafts(null)->savedDraftsOnly()->status(null)->limit(null);
            $query->setResultOverride($query->all());
        } elseif ($element && is_array($value)) {
            $query->setResultOverride($this->_createEntriesFromSerializedData($value, $element, $fromRequest));
        } elseif (request()->isPreview()) {
            $query->withProvisionalDrafts();
        }

        return $query;
    }

    /** @return EntryQuery<Entry> */
    private function createEntryQuery(?ElementInterface $owner): EntryQuery
    {
        $query = Entry::find();

        // Existing element?
        if ($owner && $owner->id) {
            $query
                ->owner($owner)
                ->excludeEagerLoadCriteria(['ownerId', 'primaryOwnerId']);

            // Clear out id=false if this query was populated previously
            if ($query->id === false) {
                $query->id = null;
            }

            // If the owner is a revision, allow revision entries to be returned as well
            if ($owner->getIsRevision()) {
                $query
                    ->revisions(null)
                    ->trashed(null);
            }

            // Prepare the query for lazy eager loading
            $query->prepForEagerLoading($this->handle, $owner);
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($owner->siteId ?? null);

        return $query;
    }

    /** @return array<int|string,array{title:string|null,slug:string|null,type:string,enabled:bool,collapsed:bool,fields:array<string,mixed>}> */
    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): array
    {
        /** @var EntryQuery<Entry>|ElementCollection<int,Entry> $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $entry) {
            /** @var Entry $entry */
            $entryId = $entry->id ?? sprintf('new%s', ++$new);
            $serialized[$entryId] = [
                'title' => $entry->title,
                'slug' => $entry->slug,
                'type' => $entry->getType()->handle,
                'enabled' => $entry->enabled,
                'collapsed' => $entry->collapsed,
                'fields' => $entry->getSerializedFieldValues(),
            ];
        }

        return $serialized;
    }

    /** @return array<int|string,array{title:string|null,slug:string|null,type:string,enabled:bool,collapsed:bool,fields:array<string,mixed>}> */
    #[Override]
    public function serializeValueForDb(mixed $value, ElementInterface $element): array
    {
        /** @var EntryQuery<Entry>|ElementCollection<int,Entry> $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $entry) {
            /** @var Entry $entry */
            $entryId = $entry->id ?? sprintf('new%s', ++$new);
            $serialized[$entryId] = [
                'title' => $entry->title,
                'slug' => $entry->slug,
                'type' => $entry->getType()->handle,
                'enabled' => $entry->enabled,
                'collapsed' => $entry->collapsed,
                'fields' => $entry->getSerializedFieldValuesForDb(),
            ];
        }

        return $serialized;
    }

    #[Override]
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    public function getElementConditionRuleType(): string
    {
        return EmptyFieldConditionRule::class;
    }

    #[Override]
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->entryManager()->getIsTranslatable($element);
    }

    #[Override]
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        return $this->entryManager()->getTranslationDescription($element);
    }

    /** @return list<array<string,mixed>> */
    #[Override]
    protected function fieldLayoutActionMenuItems(FieldLayoutElementContext $context): array
    {
        if ($this->maxEntries !== 1) {
            $items = match ($this->viewMode) {
                self::VIEW_MODE_BLOCKS => $this->blockViewActionMenuItems(),
                self::VIEW_MODE_CARDS, self::VIEW_MODE_CARDS_GRID => $this->cardViewActionMenuItems(),
                default => [],
            };
        } else {
            $items = [];
        }

        $parentItems = parent::fieldLayoutActionMenuItems($context);

        if (! empty($items) && ! empty($parentItems)) {
            return [
                ...$items,
                ['type' => 'hr'],
                ...$parentItems,
            ];
        }

        return [...$items, ...$parentItems];
    }

    /** @return list<array<string,mixed>> */
    private function blockViewActionMenuItems(): array
    {
        $items = [];

        // Expand/Collapse all. These operate on the field's input, so they're
        // excluded from chip menus, where the input may not be present (e.g.
        // the field layout designer's field-settings slideout).
        // Behavior travels with each item as a declarative action, handled by
        // the field action listeners in `resources/js/modules/fields`. The
        // listeners resolve the blocks from the invoking item's own field, so
        // no ID coordination between PHP and the Vue renderer is needed.
        $items[] = [
            'id' => sprintf('expand-all-%s', mt_rand()),
            'icon' => 'expand',
            'label' => mb_ucfirst(t('Expand all blocks', [
                'type' => Entry::pluralLowerDisplayName(),
            ])),
            'showInChips' => false,
            'action' => [
                'type' => 'event',
                'name' => 'craft:matrix-toggle-all',
                'detail' => ['collapse' => false],
            ],
        ];
        $items[] = [
            'id' => sprintf('collapse-all-%s', mt_rand()),
            'icon' => 'collapse',
            'label' => mb_ucfirst(t('Collapse all blocks', [
                'type' => Entry::pluralLowerDisplayName(),
            ])),
            'showInChips' => false,
            'action' => [
                'type' => 'event',
                'name' => 'craft:matrix-toggle-all',
                'detail' => ['collapse' => true],
            ],
        ];

        $items[] = $this->copyAction(t('blocks'), '.matrixblock');

        return $items;
    }

    /** @return list<array<string,mixed>> */
    private function cardViewActionMenuItems(): array
    {
        $items = [];

        // Copy
        $items[] = $this->copyAction(
            Entry::pluralLowerDisplayName(),
            '.nested-element-cards .elements > li > .element',
        );

        return $items;
    }

    /** @return array{id:string,icon:string,color:Color,label:string,showInChips:false,action:array<string,mixed>} */
    private function copyAction(string $type, string $entrySelector): array
    {
        return [
            'id' => sprintf('action-copy-%s', mt_rand()),
            'icon' => 'clone-dashed',
            'color' => Color::Fuchsia,
            'label' => mb_ucfirst(t('Copy all {type}', [
                'type' => $type,
            ])),
            // Operates on the field's input, which isn't present where chips
            // render (e.g. the field layout designer's settings slideout)
            'showInChips' => false,
            'action' => [
                'type' => 'event',
                'name' => 'craft:copy-nested-elements',
                'detail' => [
                    'selector' => $entrySelector,
                    'elementType' => Entry::class,
                    'fieldId' => $this->id,
                ],
            ],
        ];
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($value, $element, false);
    }

    private function inputHtmlInternal(mixed $value, ?ElementInterface $element, bool $static): string
    {
        return match ($this->viewMode) {
            self::VIEW_MODE_BLOCKS => $this->blockInputHtml($value, $element, $static),
            default => Html::tag('div', $this->nestedElementManagerHtml($element, $static), [
                'id' => $this->getInputId(),
            ]),
        };
    }

    /** @param EntryQuery<Entry>|ElementCollection<int,Entry>|null $value */
    private function blockInputHtml(EntryQuery|ElementCollection|null $value, ?ElementInterface $element, bool $static): string
    {
        if (! $element?->id) {
            $message = t('{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => Entry::pluralDisplayName(),
                'ownerType' => $element ? $element::lowerDisplayName() : t('element'),
            ]);

            return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
        }

        if ($element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof EntryQuery) {
            $value = $value->getResultOverride() ?? (clone $value)
                ->drafts(null)
                ->canonicalsOnly()
                ->status(null)
                ->limit(null)
                ->all();
        }

        if ($static && empty($value)) {
            return '<p class="light">'.t('No entries.').'</p>';
        }

        $id = $this->getInputId();
        /** @var Entry[] $value */
        $entryTypes = $this->getEntryTypesForField($value, $element);

        // Get the entry types data
        $entryTypeInfo = array_map(fn (EntryType $entryType) => [
            'id' => $entryType->id,
            'handle' => $entryType->handle,
            'name' => t($entryType->name, category: 'site'),
        ], $entryTypes);
        $createDefaultEntries = (
            $this->minEntries != 0 &&
            count($entryTypeInfo) === 1 &&
            ! $element->errors()->has($this->handle)
        );
        $staticEntries = (
            $static ||
            (
                $createDefaultEntries &&
                $this->minEntries === $this->maxEntries &&
                $this->maxEntries >= count($value)
            )
        );

        // app(InternalAssetRegistry::class)->register(MatrixAsset::class);

        $settings = [
            'fieldId' => $this->id,
            'maxEntries' => $this->maxEntries,
            'namespace' => InputNamespace::get(),
            'baseInputName' => InputNamespace::namespaceInputName($this->handle),
            'ownerElementType' => $element::class,
            'ownerId' => $element->id,
            'siteId' => $element->siteId,
            'static' => $static,
            'staticEntries' => $staticEntries,
        ];

        // Safe to create the default entries?
        if ($createDefaultEntries && count($value) < $this->minEntries) {
            // @link https://github.com/craftcms/cms/issues/12973
            // for fields with minEntries set Craft.MatrixInput.addEntry() is called before new Craft.ElementEditor(),
            // so when we get our initialSerializedValue() for the ElementEditor,
            // the entry is already there which means the field is reported as not changed since the init
            // and so not passed to PHP for save
            DeltaRegistry::setInitialValue($this->handle, null);

            $settings['addDefaultEntries'] = [
                'type' => $entryTypes[0]->handle,
                'count' => $this->minEntries - count($value),
            ];
        }

        $inputHtml = template('_components/fieldtypes/Matrix/input', [
            'id' => $id,
            'field' => $this,
            'name' => $this->handle,
            'entryTypes' => $entryTypes,
            'entries' => $value,
            'static' => $static,
            'staticEntries' => $staticEntries,
            'createButtonLabel' => $this->createButtonLabel(),
            'labelId' => $this->getLabelId(),
            'forms' => collect($value)->mapWithKeys(fn (Entry $entry): array => [
                $entry->uid => $this->blockFormVariables($entry, $static),
            ])->all(),
        ]);

        // The `<craft-matrix-input>` element (resources/js/modules/matrix)
        // boots the MatrixInput controller from these attributes, replacing the
        // imperative `new Craft.MatrixInput(...)` boot script. The attribute
        // values are written fully namespaced, since the outer namespacing pass
        // only rewrites name/id-style attributes.
        return Html::tag('craft-matrix-input', $inputHtml, [
            'entry-types' => Json::encode($entryTypeInfo),
            'input-name-prefix' => InputNamespace::namespaceInputName($this->handle),
            'settings' => Json::encode($settings),
        ]);
    }

    /** @return array{formPayload: array<string, mixed>} */
    public function blockFormVariables(Entry $entry, bool $static): array
    {
        $namespace = InputNamespace::namespaceInputName("{$this->handle}[entries][uid:{$entry->uid}]");
        $payload = app(FieldLayoutCompiler::class)->compile(
            $entry->getFieldLayout(),
            $entry,
            new FormContext(
                namespace: explode('[', str_replace(']', '', $namespace)),
                errors: $entry->errors()->getMessages(),
                mode: $static ? ControlMode::ReadOnly : ControlMode::Editable,
            ),
        );

        return [
            'formPayload' => $payload->jsonSerialize(),
        ];
    }

    private function nestedElementManagerHtml(?ElementInterface $owner, bool $static = false): string
    {
        $entryTypes = $this->_entryTypes;
        $config = [
            'showInGrid' => $this->viewMode === self::VIEW_MODE_CARDS_GRID,
            'prevalidate' => false,
        ];

        if (! $static) {
            $config += [
                'selectable' => true,
                'sortable' => true,
                'canCreate' => true,
                'canPaste' => true,
                // A pasted entry is only allowed when its entry type belongs to
                // this field. Expressed as data the client checks natively,
                // rather than predicate source code the client would eval().
                'pasteableData' => [
                    'attribute' => 'entryTypeId',
                    'values' => array_map(fn (EntryType $entryType) => $entryType->id, $entryTypes),
                ],
                'createAttributes' => array_map(fn (EntryType $entryType) => [
                    'group' => $entryType->group,
                    'icon' => $entryType->icon,
                    'color' => $entryType->color,
                    'label' => t($entryType->name, category: 'site'),
                    'attributes' => [
                        'typeId' => $entryType->id,
                    ],
                ], $entryTypes),
                'createButtonLabel' => $this->createButtonLabel(),
                'minElements' => $this->minEntries,
                'maxElements' => $this->maxEntries,
            ];

            if ($owner->errors()->has($this->handle)) {
                $config['prevalidate'] = true;
            }
        }

        if (in_array($this->viewMode, [self::VIEW_MODE_CARDS, self::VIEW_MODE_CARDS_GRID])) {
            return $this->entryManager()->getCardsHtml($owner, $config);
        }

        $config += [
            'allowedViewModes' => array_filter([
                ElementIndexViewMode::Cards,
                $this->includeTableView ? ElementIndexViewMode::Table : null,
            ]),
            'showHeaderColumn' => Collection::make($entryTypes)->contains(fn (EntryType $entryType) => (
                $entryType->hasTitleField ||
                $entryType->titleFormat ||
                ($entryType->uiLabelFormat && $entryType->uiLabelFormat !== '{title}')
            )),
            'pageSize' => $this->pageSize ?? 50,
            'storageKey' => sprintf('field:%s', $this->uid),
            'defaultViewMode' => $this->defaultIndexViewMode,
            'defaultTableColumns' => array_map(fn (string $attribute) => [$attribute], $this->defaultTableColumns),
            // field layouts are needed in the read-only (static) mode
            // so that you can choose to show columns representing the custom fields when using index view mode with table view
            'fieldLayouts' => array_map(fn (EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes),
        ];

        return $this->entryManager()->getIndexHtml($owner, $config);
    }

    private function createButtonLabel(): string
    {
        if (isset($this->createButtonLabel)) {
            return t($this->createButtonLabel, category: 'site');
        }

        return $this->defaultCreateButtonLabel();
    }

    private function defaultCreateButtonLabel(): string
    {
        return t('New {type}', [
            'type' => Entry::lowerDisplayName(),
        ]);
    }

    /** @return list<Closure> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        if (! $element->ruleset->inScenarios(ElementRules::SCENARIO_ESSENTIALS, ElementRules::SCENARIO_DEFAULT, ElementRules::SCENARIO_LIVE)) {
            return [];
        }

        return [
            fn (
                string $attribute,
                EntryQuery|ElementCollection $value,
                Closure $fail,
            ) => $this->validateEntries($element, $attribute, $value, $fail),
        ];
    }

    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var EntryQuery<Entry>|ElementCollection<int,Entry> $value */
        return $value->count() === 0;
    }

    /** @param EntryQuery<Entry>|ElementCollection<int,Entry> $value */
    private function validateEntries(ElementInterface $element, string $attribute, EntryQuery|ElementCollection $value, Closure $fail): void
    {
        $new = 0;

        if ($value instanceof EntryQuery) {
            /** @var Entry[] $entries */
            $entries = $value->getResultOverride() ?? (clone $value)
                ->drafts(null)
                ->savedDraftsOnly()
                ->status(null)
                ->limit(null)
                ->all();

            $invalidEntryIds = [];
            $scenario = $element->ruleset->getScenario();

            foreach ($entries as $entry) {
                $entry->setOwner($element);

                if (! $entry->enabled) {
                    $entry->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                } else {
                    $entry->ruleset->useScenario($scenario);
                }

                if (! $entry->validate()) {
                    // we only want to show the nested entries errors when the matrix field is in blocks view mode;
                    if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
                        $key = $entry->uid ?? sprintf('new%s', ++$new);
                        $element->addModelErrors($entry, sprintf('%s[%s]', $this->handle, $key));
                    }
                    $invalidEntryIds[] = $entry->id;
                }
            }

            if (! empty($invalidEntryIds)) {
                // Just in case the entries weren't already cached
                $value->setResultOverride($entries);
                $element->addInvalidNestedElementIds($invalidEntryIds);

                if ($this->viewMode !== self::VIEW_MODE_BLOCKS) {
                    // in card/index modes, we want to show a top level error to let users know
                    // that there are validation errors in the nested entries
                    $fail(t('Validation errors found in {count, plural, =1{one nested entry} other{{count, spellout} nested entries}} within the *{fieldName}* field; please fix them.', [
                        'count' => count($invalidEntryIds),
                        'fieldName' => $this->getUiLabel(),
                    ]));
                }
            }
        } else {
            $entries = $value->all();
        }

        if (
            $element->ruleset->inScenarios(ElementRules::SCENARIO_LIVE) &&
            ($this->minEntries || $this->maxEntries)
        ) {
            $rules = array_filter([
                $this->minEntries ? "min:$this->minEntries" : null,
                $this->maxEntries ? "max:$this->maxEntries" : null,
            ]);

            $messages = array_filter([
                "$attribute.min" => $this->minEntries ? t('{attribute} should contain at least {min, number} {min, plural, one{entry} other{entries}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'min' => $this->minEntries, // Need to pass this in now
                ]) : null,
                "$attribute.max" => $this->maxEntries ? t('{attribute} should contain at most {max, number} {max, plural, one{entry} other{entries}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'max' => $this->maxEntries, // Need to pass this in now
                ]) : null,
            ]);

            $v = ValidatorFacade::make(
                data: [$attribute => $entries],
                rules: [$attribute => $rules],
                messages: $messages
            );

            foreach ($v->errors()->get($attribute) as $error) {
                $fail($error);
            }
        }
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->entryManager()->getSearchKeywords($element);
    }

    /** @return array{elementType:class-string<Entry>,map:list<array{source:int,target:int}>,criteria:array{fieldId:int|string|null,allowOwnerDrafts:true,allowOwnerRevisions:true,revisions:bool},createElement:callable} */
    public function getEagerLoadingMap(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = DB::table(Table::ENTRIES, 'entries')
            ->select([
                'elements_owners.ownerId as source',
                'entries.id as target',
            ])
            ->join(new Alias(Table::ELEMENTS_OWNERS, 'elements_owners'), function (JoinClause $join) use ($sourceElementIds) {
                $join->whereColumn('elements_owners.elementId', 'entries.id')
                    ->whereIn('elements_owners.ownerId', $sourceElementIds);
            })
            ->where('entries.fieldId', $this->id)
            ->orderBy('elements_owners.sortOrder')
            ->get()
            ->map(fn (object $row) => (array) $row)
            ->all();

        return [
            'elementType' => Entry::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
                // only include revisions if any of the source elements is a revision
                // see https://github.com/craftcms/cms/issues/14448 and https://github.com/craftcms/cms/issues/17324
                'revisions' => Collection::make($sourceElements)
                    ->contains(fn ($sourceElement) => $sourceElement->getIsRevision()),
            ],
            'createElement' => fn (EntryQuery $query, array $result, ElementInterface $sourceElement) => $query
                ->owner($sourceElement)
                ->createElement($result),
        ];
    }

    #[Override]
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool
    {
        if (! $outgoingField instanceof self) {
            $reason = 'Matrix fields can only be merged into other Matrix fields.';

            return false;
        }

        // Make sure this field has all the entry types the outgoing field has
        $outgoingEntryTypeIds = array_map(fn (EntryType $entryType) => $entryType->id, $outgoingField->getEntryTypes());
        $persistentEntryTypeIds = array_map(fn (EntryType $entryType) => $entryType->id, $this->_entryTypes);
        $missingEntryTypeIds = array_diff($outgoingEntryTypeIds, $persistentEntryTypeIds);
        if (! empty($missingEntryTypeIds)) {
            $reason = "$this->name doesn’t have all of the entry types that $outgoingField->name does.";

            return false;
        }

        return true;
    }

    #[Override]
    public function afterMergeFrom(FieldInterface $outgoingField): void
    {
        DB::table(Table::ENTRIES)
            ->where('fieldId', $outgoingField->id)
            ->update([
                'fieldId' => $this->id,
                'dateUpdated' => now(),
            ]);

        parent::afterMergeFrom($outgoingField);
    }

    /** @return array{name:string|null,type:Type,args:array<string,Type|ArgumentConfig>,resolve:string,complexity:callable} */
    #[Override]
    public function getContentGqlType(): array
    {
        $typeArray = EntryTypeGenerator::generateTypes($this);
        $typeName = $this->handle.'_MatrixField';

        $arguments = EntryArguments::getArguments();

        foreach ($this->_entryTypes as $entryType) {
            $arguments += Gql::getFieldLayoutArguments($entryType->getFieldLayout());
        }

        $unionType = GqlHelper::getUnionType($typeName, $typeArray);

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf($unionType)),
            'args' => $arguments,
            'resolve' => EntryResolver::class.'::resolve',
            'complexity' => GqlHelper::eagerLoadComplexity(),
        ];
    }

    /** @return array{withProvisionalDrafts:bool} */
    #[Override]
    public function getEagerLoadingGqlConditions(): array
    {
        return [
            'withProvisionalDrafts' => request()->isPreview(),
        ];
    }

    #[Override]
    public function getContentGqlMutationArgumentType(): Type
    {
        return MatrixInputType::getType($this);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface
    {
        $entryTypeHandle = Str::between($fragmentName, $this->handle.'_', '_Entry');

        $entryType = Collection::make($this->_entryTypes)->firstWhere('handle', $entryTypeHandle);

        if (! $entryType) {
            throw new InvalidArgumentException('Invalid fragment name: '.$fragmentName);
        }

        return $entryType;
    }

    // Events
    // -------------------------------------------------------------------------

    #[Override]
    public function afterSave(bool $isNew): void
    {
        // If the propagation method or an entry URI format just changed, resave all the entries
        if (isset($this->oldSettings)) {
            $oldPropagationMethod = PropagationMethod::tryFrom($this->oldSettings['propagationMethod'] ?? '')
                ?? PropagationMethod::All;
            $oldPropagationKeyFormat = $this->oldSettings['propagationKeyFormat'] ?? null;
            if ($this->propagationMethod !== $oldPropagationMethod || $this->propagationKeyFormat !== $oldPropagationKeyFormat) {
                dispatch(new ApplyNewPropagationMethod(
                    elementType: Entry::class,
                    criteria: [
                        'fieldId' => $this->id,
                    ],
                    description: I18N::prep('Applying new propagation method to {name} entries', [
                        'name' => $this->name,
                    ]),
                ));
            } else {
                $resaveSiteIds = [];

                foreach (Sites::getAllSites(true) as $site) {
                    $oldUriFormat = $this->oldSettings['siteSettings'][$site->uid]['uriFormat'] ?? null;
                    $newUriFormat = $this->siteSettings[$site->uid]['uriFormat'] ?? null;
                    if ($oldUriFormat !== $newUriFormat) {
                        $resaveSiteIds[] = $site->id;
                    }
                }

                if (! empty($resaveSiteIds)) {
                    dispatch(new ResaveElements(
                        elementType: Entry::class,
                        criteria: [
                            'fieldId' => $this->id,
                            'siteId' => $resaveSiteIds,
                            'unique' => true,
                            'status' => null,
                            'drafts' => null,
                            'provisionalDrafts' => null,
                            'revisions' => null,
                        ],
                        description: I18N::prep('Resaving {name} entries', [
                            'name' => $this->name,
                        ]),
                    ));
                }
            }
        }

        parent::afterSave($isNew);
    }

    #[Override]
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->entryManager()->maintainNestedElements($element, $isNew);
        parent::afterElementPropagate($element, $isNew);
    }

    /**
     * Handles nested entry saves.
     */
    public function afterSaveEntries(NestedElementsSaved $event): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        // Tell the browser to collapse any new entry IDs
        $collapsedIds = Collection::make($event->elements)
            ->filter(fn (ElementInterface $entry) => $entry instanceof Entry && $entry->collapsed)
            ->map(fn (ElementInterface $entry) => $entry->id)
            ->all();

        if (empty($collapsedIds)) {
            return;
        }

        app(InternalAssetRegistry::class)->flash(MatrixAsset::class);

        foreach ($collapsedIds as $id) {
            session()->flashJs("Craft.MatrixInput.rememberCollapsedEntryId($id);", Position::BodyEnd);
        }
    }

    #[Override]
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (! parent::beforeElementDelete($element)) {
            return false;
        }

        // Delete any entries that primarily belong to this element
        $this->entryManager()->deleteNestedElements($element, $element->hardDelete);

        return true;
    }

    #[Override]
    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->primaryOwner($element)
            ->status(null)
            ->all();

        Elements::deleteElementsForSite($entries);

        return true;
    }

    #[Override]
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any entries for this element
        $this->entryManager()->restoreNestedElements($element);

        parent::afterElementRestore($element);
    }

    /**
     * Creates an array of entries based on the given serialized data.
     *
     * @param  ElementInterface  $element  The element the field is associated with
     * @param  bool  $fromRequest  Whether the data came from the request post data
     *
     * @phpstan-param SerializedEntries|array{entries?:SerializedEntries,blocks?:SerializedEntries,sortOrder?:list<int|string>} $value The raw field value
     *
     * @return Entry[]
     */
    private function _createEntriesFromSerializedData(array $value, ElementInterface $element, bool $fromRequest): array
    {
        // Get the possible entry types for this field
        /** @var EntryType[] $entryTypes */
        $entryTypes = Arr::keyBy($this->_entryTypes, 'handle');

        // Were the entries posted by UUID or ID?
        $uids = (
            (isset($value['entries']) && str_starts_with((string) array_key_first($value['entries']), 'uid:')) ||
            (isset($value['sortOrder']) && Str::isUuid(reset($value['sortOrder'])))
        );

        if ($uids) {
            // strip out the `uid:` key prefixes
            if (isset($value['entries'])) {
                $value['entries'] = array_combine(
                    array_map(fn (string $key) => Str::chopStart($key, 'uid:'), array_keys($value['entries'])),
                    array_values($value['entries']),
                );
            }
        }

        // Get the old entries
        if ($element->id) {
            /** @var Entry[] $oldEntriesById */
            $oldEntriesById = Entry::find()
                ->fieldId($this->id)
                ->owner($element)
                ->drafts(null)
                ->status(null)
                ->get()
                ->keyBy($uids ? 'uid' : 'id')
                ->all();
        } else {
            $oldEntriesById = [];
        }

        if ($uids) {
            // Get the canonical entry UUIDs in case the data was posted with them
            $derivatives = Collection::make($oldEntriesById)
                ->filter(fn (Entry $entry) => $entry->getIsDerivative())
                ->keyBy(fn (Entry $entry) => $entry->getCanonicalId());

            if ($derivatives->isNotEmpty()) {
                $canonicalUids = DB::table(Table::ELEMENTS)
                    ->select(['id', 'uid'])
                    ->whereIn('id', $derivatives->keys())
                    ->pluck('uid', 'id');

                $derivativeUidMap = [];
                $canonicalUidMap = [];
                foreach ($canonicalUids as $canonicalId => $canonicalUid) {
                    $derivativeUid = $derivatives->get($canonicalId)->uid;
                    $derivativeUidMap[$canonicalUid] = $derivativeUid;
                    $canonicalUidMap[$derivativeUid] = $canonicalUid;
                }
            }
        }

        // Should we ignore disabled entries?
        $request = request();
        $hideDisabledEntries = ! app()->runningInConsole() && (
            $request->getToken() !== null ||
            $request->isPreview()
        );

        $entries = [];
        $prevEntry = null;

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseEntryFieldNamespace = $fieldNamespace ? "$fieldNamespace.$this->handle" : null;

        // Was the value posted in the new (delta) format?
        if (isset($value['entries']) || isset($value['blocks']) || isset($value['sortOrder'])) {
            $newEntryData = $value['entries'] ?? $value['blocks'] ?? [];
            $newSortOrder = $value['sortOrder'] ?? array_keys($oldEntriesById);
            if ($baseEntryFieldNamespace) {
                $baseEntryFieldNamespace .= '.entries';
            }
        } else {
            $newEntryData = $value;
            $newSortOrder = array_keys($value);
        }

        foreach ($newSortOrder as $entryId) {
            if (isset($newEntryData[$entryId])) {
                $entryData = $newEntryData[$entryId];
            } elseif (
                $uids &&
                isset($canonicalUidMap[$entryId]) &&
                isset($newEntryData[$canonicalUidMap[$entryId]])
            ) {
                // $entryId is a draft entry's UUID, but the data was sent with the canonical entry UUID
                $entryData = $newEntryData[$canonicalUidMap[$entryId]];
            } else {
                $entryData = [];
            }

            // If this is a preexisting entry but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                $uids &&
                ! isset($oldEntriesById[$entryId]) &&
                isset($derivativeUidMap[$entryId]) &&
                isset($oldEntriesById[$derivativeUidMap[$entryId]])
            ) {
                $entryId = $derivativeUidMap[$entryId];
            }

            // Existing entry?
            if (isset($oldEntriesById[$entryId])) {
                $entry = $oldEntriesById[$entryId];
                $forceSave = ! empty($entryData);

                // Is this a derivative element, and does the entry primarily belong to the canonical?
                if (
                    $forceSave &&
                    $element->getIsDerivative() &&
                    ElementHelper::belongsToCanonicalOwner($entry, $element) &&
                    // this is so that extra drafts don't get created for matrix in matrix scenario
                    // where both are set to inline-editable blocks view mode
                    (
                        app()->runningInConsole() ||
                        request()->actionSegments() !== ['elements', 'update-field-layout']
                    )
                ) {
                    // Duplicate it as a draft. (We'll drop its draft status from NestedElementManager::saveNestedElements().)
                    $entry = app(Drafts::class)->createDraft($entry, Auth::id(), null, null, [
                        'canonicalId' => $entry->id,
                        'primaryOwnerId' => $element->id,
                        'owner' => $element,
                        'siteId' => $element->siteId,
                        'propagating' => false,
                        'markAsSaved' => false,
                    ]);
                }

                $entry->forceSave = $forceSave;
            } else {
                // Make sure it's a valid entry type
                if (! isset($entryData['type'])) {
                    continue;
                }
                if (! isset($entryTypes[$entryData['type']])) {
                    continue;
                }
                $entry = new Entry;
                $entry->fieldId = $this->id;
                $entry->typeId = $entryTypes[$entryData['type']]->id;
                $entry->setPrimaryOwner($element);
                $entry->setOwner($element);
                $entry->siteId = $element->siteId;

                // Use the provided UUID, so the block can persist across future autosaves
                if ($uids) {
                    $entry->uid = $entryId;
                }

                // Preserve the collapsed state, which the browser can't remember on its own for new entries
                $entry->collapsed = ! empty($entryData['collapsed']);
            }

            if (isset($entryData['enabled'])) {
                $entry->enabled = (bool) $entryData['enabled'];
            }

            if (isset($entryData['fresh'])) {
                $entry->setIsFresh();
                $entry->propagateAll = true;
            }

            if (array_key_exists('title', $entryData) && $entry->getType()->hasTitleField) {
                $entry->title = $entryData['title'];
            }

            if (array_key_exists('slug', $entryData) && $entry->getType()->showSlugField) {
                $entry->slug = $entryData['slug'];
            }

            // Allow setting the UID for the entry
            if (array_key_exists('uid', $entryData)) {
                $entry->uid = $entryData['uid'];
            }

            // Skip disabled entries on Live Preview requests
            if ($hideDisabledEntries && ! $entry->enabled) {
                continue;
            }

            $entry->setOwner($element);

            // Set the content post location on the entry if we can
            if ($baseEntryFieldNamespace) {
                if ($uids) {
                    $entry->setFieldParamNamespace("$baseEntryFieldNamespace.uid:$entryId.fields");
                } else {
                    $entry->setFieldParamNamespace("$baseEntryFieldNamespace.$entryId.fields");
                }
            }

            if (isset($entryData['fields'])) {
                foreach ($entryData['fields'] as $fieldHandle => $fieldValue) {
                    try {
                        if ($fromRequest) {
                            $entry->setFieldValueFromRequest($fieldHandle, $fieldValue);
                        } else {
                            $entry->setFieldValue($fieldHandle, $fieldValue);
                        }
                    } catch (InvalidFieldException) {
                    }
                }
            }

            // Set the prev/next entries
            if ($prevEntry) {
                /** @var ElementInterface $prevEntry */
                $prevEntry->setNext($entry);
                /** @var ElementInterface $entry */
                $entry->setPrev($prevEntry);
            }
            $prevEntry = $entry;

            $entries[] = $entry;
        }

        /** @var Entry[] $entries */
        return $entries;
    }
}
