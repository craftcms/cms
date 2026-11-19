<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Cp\Enums\ChipThumbSize;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Builds the settings Ui for a single element index source, as shown in the
 * “Customize sources” modal.
 *
 * Each source's Ui is namespaced at `sources.<key>`, so a Control at
 * `label` posts as `sources[<key>][label]` — the shape
 * {@see ElementSourcesController::store()}
 * reads.
 *
 * @since 6.0.0
 */
readonly class ElementSourceUi
{
    public function __construct(
        private ElementSources $elementSources,
        private Fields $fields,
        private UserGroups $userGroups,
        private Sites $sites,
        private UiResolver $resolver,
        private StatusHtml $statusHtml,
    ) {}

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source  a source config from {@see ElementSources::getSources()}
     * @param  array<string, mixed>  $values  posted settings; seeded from $source when empty
     * @param  bool  $isNew  whether the source has yet to be saved
     * @param  bool  $withUserGroups  false when the caller asks who can see the source itself
     * @param  bool  $withLabel  false when the caller asks for the source's name itself
     * @param  bool  $forProjectConfig  false to build the criteria like an index's filters, with
     *                                  element pickers and every rule, rather than only what project config can hold
     * @param  list<'filters'|'sort'|'columns'|'viewMode'>  $edited  parts that differ from the source
     *                                                               they were taken from, marked as edited
     * @param  list<string>  $droppedRules  labels of filters left out because project config can't hold them
     */
    public function payload(string $elementType, array $source, array $values = [], bool $isNew = false, bool $withUserGroups = true, bool $withLabel = true, bool $forProjectConfig = true, ?string $part = null, array $edited = [], array $droppedRules = []): UiPayload
    {
        if (! isset($source['key']) || $source['key'] === '') {
            throw new InvalidArgumentException('Element source UIs require a source key.');
        }

        $key = (string) $source['key'];
        // A new source can start from part of a view, such as the index's
        // current filters, and take defaults for the rest.
        $values = $isNew
            ? array_replace($this->seed($elementType, $source), $values)
            : ($values ?: $this->seed($elementType, $source));

        // Structure order only runs one way. The direction control is disabled
        // for it, and a disabled control takes the value sent back here.
        if (($values['defaultSort']['attr'] ?? null) === 'structure') {
            $values['defaultSort']['dir'] = 'asc';
        }

        return $this->resolver->resolve(
            $this->ui($elementType, $source, $values, $isNew, $withUserGroups, $withLabel, $forProjectConfig, $part, $edited, $droppedRules),
            new UiContext(
                namespace: ['sources', $key],
                values: ['sources' => [$key => $values]],
                refreshable: true,
            ),
        );
    }

    /**
     * Splits an index's filters into those a source in project config can
     * hold and the labels of those it can't, such as an ID rule.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $condition
     * @return array{condition: array<string, mixed>, dropped: list<string>}
     */
    public function storableCondition(string $elementType, array $condition): array
    {
        $dropped = [];
        $rules = $this->storableRules($elementType, $condition['conditionRules'] ?? [], $dropped);

        return [
            'condition' => ['conditionRules' => $rules] + $condition,
            'dropped' => $dropped,
        ];
    }

    /**
     * Each rule is tried alongside the rules kept before it, since whether a
     * rule can be chosen may depend on its siblings.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $dropped
     * @param  list<mixed>  $context  rules kept so far, at any depth
     */
    private function storableRules(string $elementType, mixed $rules, array &$dropped, array &$context = []): mixed
    {
        if (! is_array($rules)) {
            return $rules;
        }

        $isGroup = isset($rules['operator']) && ! isset($rules['class']);
        $kept = [];

        foreach ($isGroup ? ($rules['rules'] ?? []) : $rules as $rule) {
            if (is_array($rule) && ! isset($rule['class']) && isset($rule['rules'])) {
                $group = $this->storableRules($elementType, $rule, $dropped, $context);
                if ($group['rules'] !== []) {
                    $kept[] = $group;
                }

                continue;
            }

            if ($this->storableRule($elementType, $rule, $context)) {
                $kept[] = $rule;
                $context[] = $rule;

                continue;
            }

            $dropped[] = $this->isRuleConfig($rule)
                ? Conditions::createConditionRule($rule)->getLabel()
                : t('Filter');
        }

        return $isGroup ? ['rules' => $kept] + $rules : $kept;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<mixed>  $context
     */
    private function storableRule(string $elementType, mixed $rule, array $context): bool
    {
        // A condition skips an unknown rule class without an error.
        if (! $this->isRuleConfig($rule)) {
            return false;
        }

        $condition = $elementType::createCondition();
        $condition->forProjectConfig = true;

        try {
            $condition->setConditionRules([...$context, $rule]);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /** @phpstan-assert-if-true array{class: class-string<ConditionRuleInterface>} $rule */
    private function isRuleConfig(mixed $rule): bool
    {
        return is_array($rule)
            && is_string($rule['class'] ?? null)
            && is_subclass_of($rule['class'], ConditionRuleInterface::class);
    }

    /**
     * A config for a source the client just invented, so its Ui can be built
     * before it exists in project config.
     *
     * @return array<string, mixed>
     */
    public function blankSource(string $type, string $key): array
    {
        return [
            'type' => $type,
            'key' => $key,
            'label' => '',
            'heading' => '',
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @param  list<string>  $edited
     * @param  list<string>  $droppedRules
     */
    private function ui(string $elementType, array $source, array $values, bool $isNew, bool $withUserGroups, bool $withLabel, bool $forProjectConfig, ?string $part, array $edited, array $droppedRules): Ui
    {
        return Ui::make(array_values(array_filter(match ($source['type'] ?? ElementSources::TYPE_NATIVE) {
            ElementSources::TYPE_HEADING => $this->headingNodes(),
            ElementSources::TYPE_CUSTOM => $this->customNodes($elementType, $source, $values, $isNew, $withUserGroups, $withLabel, $forProjectConfig, $part, $edited, $droppedRules),
            default => $this->nativeNodes($elementType, $source, $values),
        })));
    }

    /** @return list<Field|null> */
    private function headingNodes(): array
    {
        return [
            Field::make(t('Heading'), Text::make('heading'))
                ->instructions(t('This can be left blank if you just want an unlabeled separator.')),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @return list<Node|null>
     */
    private function nativeNodes(string $elementType, array $source, array $values): array
    {
        return [
            Field::make(t('Enabled'), Lightswitch::make('enabled')),
            $this->viewModeField($elementType, $source),
            ...$this->thumbSizeFields($elementType, $source),
            ...$this->sortFields($elementType, $source, $values),
            $this->tableAttributesField($elementType, $source, false),
            $this->badgeCountField($elementType, $source),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @param  list<string>  $edited
     * @param  list<string>  $droppedRules
     * @return list<Node|null>
     */
    private function customNodes(string $elementType, array $source, array $values, bool $isNew, bool $withUserGroups, bool $withLabel, bool $forProjectConfig, ?string $part = null, array $edited = [], array $droppedRules = []): array
    {
        $badge = $this->statusHtml->editedStatusLabelHtml(size: 'small');
        $mark = fn (?Field $field, string $part) => $field && in_array($part, $edited, true)
            ? $field->headingSuffix($badge)
            : $field;

        $condition = $elementType::createCondition();
        $filters = Field::make(
            t('{type} Criteria', ['type' => $elementType::displayName()]),
            ConditionBuilder::make('condition')
                ->conditionClass($condition::class)
                ->queryParams(['site', 'status'])
                ->forProjectConfig($forProjectConfig)
                ->addRuleLabel(t('Add a filter')),
        );
        $mark($filters, 'filters');
        if ($droppedRules !== []) {
            $filters->warning(t('Left out, since a saved source can’t hold them: {rules}', [
                'rules' => implode(', ', array_unique($droppedRules)),
            ]));
        }
        // An index's own view is what it shows now, not a default.
        $current = $part === 'view';
        [$sort, $direction] = $this->sortFields($elementType, $source, $values, $current);
        $view = [
            $mark($this->viewModeField($elementType, $source, $current), 'viewMode'),
            ...array_map(fn (Field $field) => $mark($field, 'thumbSize'), $this->thumbSizeFields($elementType, $source, $current)),
            $mark($sort, 'sort'),
            $direction,
            $mark($this->tableAttributesField($elementType, $source, $isNew, $current), 'columns'),
        ];

        // Just the filters, or just how they're shown, for an index's settings.
        if ($part === 'filters') {
            return [$filters];
        }
        if ($part === 'view') {
            return $view;
        }

        $siteOptions = $this->sites->getAllSites()
            ->map(fn (Site $site) => ['label' => t($site->name, category: 'site'), 'value' => $site->uid])
            ->values()
            ->all();
        $groupOptions = $withUserGroups ? $this->userGroupOptions() : [];

        return [
            $withLabel ? Field::make(t('Label'), Text::make('label')) : null,
            // Only a new source can be kept to yourself, which lets its filters
            // hold rules project config can't.
            $isNew && $withUserGroups
                ? Group::make('source-filters', [$filters])->dependsOn('visibility')
                : $filters,
            ...$view,
            count($siteOptions) > 1
                ? Field::make(t('Sites'), Choice::make('sites')->options($siteOptions)->allOption())
                    ->instructions(t('Choose which sites this source should be visible for.'))
                : null,
            ...($withUserGroups ? $this->visibilityNodes($values, $groupOptions, $isNew) : []),
            $this->badgeCountField($elementType, $source),
        ];
    }

    /**
     * Who sees the source, with the user groups to pick from only when it's
     * for specific ones. Only a new source can be kept to yourself; one
     * already in project config stays there.
     *
     * @param  array<string, mixed>  $values
     * @param  list<array{label: string, value: string}>  $groupOptions
     * @return list<Node>
     */
    private function visibilityNodes(array $values, array $groupOptions, bool $isNew): array
    {
        $options = [
            ...($isNew ? [['label' => t('Only me'), 'value' => 'me', 'description' => t('Listed with your own views in the navigation.')]] : []),
            ['label' => t('Everyone'), 'value' => 'everyone', 'description' => t('Saved to project config, like any custom source.')],
        ];

        if ($groupOptions !== []) {
            $options[] = ['label' => t('Specific user groups'), 'value' => 'groups', 'description' => t('Saved to project config, for admins and the groups you choose.')];
        }

        $nodes = [
            Field::make(t('Visible to'), Choice::make('visibility')
                ->options($options)
                ->presentation(ChoicePresentation::Radios)
                ->reactive()),
        ];

        if ($groupOptions !== []) {
            $nodes[] = Group::make('source-user-groups', [
                Field::make(t('User Groups'), Choice::make('userGroups')->options($groupOptions)->allOption())
                    ->instructions(t('Choose which user groups should have access to this source.')),
            ])
                ->dependsOn('visibility')
                ->visible(($values['visibility'] ?? null) === 'groups');
        }

        return $nodes;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function badgeCountField(string $elementType, array $source): ?Field
    {
        if (! $elementType::sourceSupportsBadgeCount($source)) {
            return null;
        }

        return Field::make(t('Show count'), Lightswitch::make('showBadgeCount'))
            ->instructions(t('Whether the number of elements in this source should be shown beside it in the navigation.'));
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function viewModeField(string $elementType, array $source, bool $current = false): Field
    {
        $options = array_map(fn (array $viewMode) => [
            // The icon is presentational, so the title becomes the button's
            // accessible name.
            'label' => $viewMode['title'],
            'icon' => $viewMode['icon'] ?? 'table',
            'value' => $viewMode['mode'],
        ], $this->viewModes($elementType, $source));

        return Field::make(
            $current ? t('View Mode') : t('Default View Mode'),
            Choice::make('defaultViewMode')->options($options)->presentation(ChoicePresentation::Buttons),
        );
    }

    /**
     * A thumbnail size for each of the source's views that shows thumbnails:
     * table rows, and thumbnail tiles where the element type has them.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return list<Field>
     */
    private function thumbSizeFields(string $elementType, array $source, bool $current = false): array
    {
        $titles = array_column($this->viewModes($elementType, $source), 'title', 'mode');
        $views = array_filter([
            'table' => $titles[ElementIndexViewMode::Table->value] ?? null,
            'thumbs' => $titles[ElementIndexViewMode::Thumbs->value] ?? null,
        ]);
        $label = $current ? t('Thumb Size') : t('Default Thumb Size');

        return array_values(array_map(
            fn (string $title, string $view) => Field::make(
                count($views) > 1 ? t('{label} ({viewMode})', ['label' => $label, 'viewMode' => $title]) : $label,
                Choice::make("defaultThumbSizes.$view")
                    ->options(array_map(fn (string $size) => [
                        'label' => ChipThumbSize::from($size)->label(),
                        'value' => $size,
                    ], ChipThumbSize::INDEX_VIEW_SIZES[$view]))
                    ->presentation(ChoicePresentation::Buttons),
            ),
            $views,
            array_keys($views),
        ));
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @return array{0: Field, 1: Group}
     */
    private function sortFields(string $elementType, array $source, array $values, bool $current = false): array
    {
        $options = $this->sortOptions($elementType, $source);
        $dir = Choice::make('defaultSort.dir')
            ->options([
                ['label' => t('Sort ascending'), 'icon' => 'asc', 'value' => 'asc'],
                ['label' => t('Sort descending'), 'icon' => 'desc', 'value' => 'desc'],
            ])
            ->presentation(ChoicePresentation::Buttons);

        // Structure order has no direction to pick.
        if (($values['defaultSort']['attr'] ?? null) === 'structure') {
            $dir->mode(ControlMode::Disabled);
        }

        return [
            Field::make($current ? t('Sort') : t('Default Sort'), Choice::make('defaultSort.attr')->options(array_map(
                fn (array $option) => [
                    'label' => $option['label'],
                    'value' => $option['attr'],
                    // Custom fields sit apart, under their own heading.
                    'group' => str_starts_with((string) $option['attr'], 'field:') ? t('Fields') : null,
                    // The direction a newly chosen attribute starts in.
                    'defaultDir' => $option['defaultDir'],
                ],
                $options,
            ))->reactive())->width(75),
            Group::make('source-sort-direction', [
                Field::make(t('Sort Direction'), $dir),
            ])
                ->width(25)
                ->dependsOn('defaultSort.attr'),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function tableAttributesField(string $elementType, array $source, bool $isNew, bool $current = false): ?Field
    {
        $options = $this->tableAttributeOptions($elementType, $source, $isNew);

        if ($options === []) {
            return null;
        }

        $field = Field::make(
            $current ? t('Table Columns') : t('Default Table Columns'),
            Choice::make('tableAttributes')
                ->options($options)
                ->sortable()
                ->presentation(ChoicePresentation::AddMenu),
        );

        return $current
            ? $field
            : $field->instructions(t('Choose which table columns should be visible for this source by default.'));
    }

    /**
     * Available columns, alphabetized. New custom sources also offer every
     * previewable custom field, since their field layouts aren't known yet.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return list<array{label: string, value: string}>
     */
    private function tableAttributeOptions(string $elementType, array $source, bool $isNew): array
    {
        $attributes = $this->elementSources->getAvailableTableAttributes($elementType)
            ->merge($this->elementSources->getSourceTableAttributes($elementType, (string) $source['key']))
            ->map(fn (array $labelInfo, string $key) => ['label' => $labelInfo['label'], 'value' => $key]);

        if ($isNew && ($source['type'] ?? null) === ElementSources::TYPE_CUSTOM) {
            $attributes = $attributes->merge($this->previewableFieldAttributes($elementType));
        }

        return $attributes
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, array{label: string, value: string}>
     */
    private function previewableFieldAttributes(string $elementType): array
    {
        $attributes = [];

        foreach ($this->fields->getLayoutsByType($elementType) as $fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field instanceof PreviewableFieldInterface) {
                    $attributes["field:$field->uid"] = [
                        'label' => t($field->name, category: 'site'),
                        'value' => "field:$field->uid",
                    ];
                }
            }
        }

        return $attributes;
    }

    /**
     * Sort options, alphabetized, with structure first and custom fields last —
     * the grouping the legacy modal rendered as optgroups.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return list<array{label: string, attr: string, defaultDir: string}>
     */
    private function sortOptions(string $elementType, array $source): array
    {
        $options = collect($elementType::sortOptions())
            ->map(fn ($option, $key) => [
                'label' => $option['label'] ?? $option,
                'attr' => $option['attribute'] ?? $option['orderBy'] ?? $key,
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->concat($this->elementSources->getSourceSortOptions($elementType, (string) $source['key'])
                ->map(fn (array $option) => [
                    'label' => $option['label'],
                    'attr' => $option['attribute'] ?? $option['orderBy'],
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ]))
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->sortBy(fn (array $option) => str_starts_with((string) $option['attr'], 'field:') ? 1 : 0)
            ->values()
            ->all();

        if ($source['structureId'] ?? false) {
            array_unshift($options, [
                'label' => t('Structure'),
                'attr' => 'structure',
                'defaultDir' => 'asc',
            ]);
        }

        return $options;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return list<array<string, mixed>>
     */
    private function viewModes(string $elementType, array $source): array
    {
        return array_values(array_filter(
            $elementType::indexViewModes(),
            fn (array $viewMode) => empty($viewMode['structuresOnly']) || ($source['structureId'] ?? false),
        ));
    }

    /** @return list<array{label: string, value: string}> */
    public function userGroupOptions(): array
    {
        return $this->userGroups->getAllGroups()
            ->map(fn (UserGroup $group) => [
                'label' => t($group->name, category: 'site'),
                'value' => $group->uid,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function seed(string $elementType, array $source): array
    {
        if (($source['type'] ?? null) === ElementSources::TYPE_HEADING) {
            return ['heading' => $source['heading'] ?? ''];
        }

        $shared = [
            'defaultSort' => $this->seedSort($elementType, $source),
            'defaultViewMode' => $this->seedViewMode($elementType, $source),
            'defaultThumbSizes' => ChipThumbSize::indexDefaults($source['defaultThumbSizes'] ?? null),
            'tableAttributes' => $this->seedTableAttributes($elementType, $source),
        ];

        if ($elementType::sourceSupportsBadgeCount($source)) {
            $shared['showBadgeCount'] = $this->elementSources->showsBadgeCount($elementType, $source);
        }

        if (($source['type'] ?? null) !== ElementSources::TYPE_CUSTOM) {
            return ['enabled' => empty($source['disabled'])] + $shared;
        }

        return [
            'label' => $source['label'] ?? '',
            // Never seed an empty condition: the builder only re-reports its
            // value once someone edits it, so an untouched UI must post back
            // a config that already carries its class.
            'condition' => $source['condition'] ?? $elementType::createCondition()->getConfig(),
            'sites' => $this->seedScope($source, 'sites'),
            'userGroups' => $this->seedScope($source, 'userGroups'),
            'visibility' => isset($source['userGroups']) ? 'groups' : 'everyone',
        ] + $shared;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return array{attr: string|null, dir: string}
     */
    private function seedSort(string $elementType, array $source): array
    {
        $options = $this->sortOptions($elementType, $source);
        $stored = $source['defaultSort'] ?? null;
        $attr = is_array($stored) ? ($stored[0] ?? null) : $stored;
        $dir = is_array($stored) ? ($stored[1] ?? null) : null;

        $option = collect($options)->firstWhere('attr', $attr) ?? reset($options) ?: null;

        return [
            'attr' => $option['attr'] ?? null,
            'dir' => ($option && $option['attr'] === $attr ? $dir : null) ?? $option['defaultDir'] ?? 'asc',
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function seedViewMode(string $elementType, array $source): ?string
    {
        $viewModes = $this->viewModes($elementType, $source);
        $stored = $source['defaultViewMode'] ?? null;

        foreach ($viewModes as $viewMode) {
            if ($viewMode['mode'] === $stored) {
                return $stored;
            }
        }

        return $viewModes[0]['mode'] ?? null;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @return list<string>
     */
    private function seedTableAttributes(string $elementType, array $source): array
    {
        $attributes = $this->elementSources->getTableAttributes($elementType, (string) $source['key'])->all();

        // The first column is always the title, and isn't selectable.
        array_shift($attributes);

        return array_map(fn (array $attribute) => (string) $attribute[0], array_values($attributes));
    }

    /**
     * An absent key means “all”, which project config records by omission.
     *
     * @param  array<string, mixed>  $source
     * @return list<string>|string
     */
    private function seedScope(array $source, string $key): array|string
    {
        if (! array_key_exists($key, $source)) {
            return Choice::ALL_VALUE;
        }

        if ($source[$key] === false) {
            return [];
        }

        $values = is_array($source[$key]) ? $source[$key] : [];

        if ($key === 'sites') {
            $values = array_filter(array_map(
                fn (mixed $site) => is_int($site) ? $this->sites->getSiteById($site)?->uid : (string) $site,
                $values,
            ));
        }

        return array_values($values);
    }
}
