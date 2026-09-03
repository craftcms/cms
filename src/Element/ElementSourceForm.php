<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\ConditionBuilder;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Builds the settings Form for a single element index source, as shown in the
 * “Customize sources” modal.
 *
 * Each source's Form is namespaced at `sources.<key>`, so a Control at
 * `label` posts as `sources[<key>][label]` — the shape
 * {@see ElementSourcesController::store()}
 * reads.
 */
readonly class ElementSourceForm
{
    public function __construct(
        private ElementSources $elementSources,
        private Fields $fields,
        private UserGroups $userGroups,
        private Sites $sites,
        private FormResolver $resolver,
    ) {}

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source  a source config from {@see ElementSources::getSources()}
     * @param  array<string, mixed>  $values  posted settings; seeded from $source when empty
     * @param  bool  $isNew  whether the source has yet to be saved
     */
    public function payload(string $elementType, array $source, array $values = [], bool $isNew = false): FormPayload
    {
        if (! isset($source['key']) || $source['key'] === '') {
            throw new InvalidArgumentException('Element source Forms require a source key.');
        }

        $key = (string) $source['key'];
        $values = $values ?: $this->seed($elementType, $source);

        return $this->resolver->resolve(
            $this->form($elementType, $source, $values, $isNew),
            new FormContext(
                namespace: ['sources', $key],
                values: ['sources' => [$key => $values]],
                refreshable: true,
            ),
        );
    }

    /**
     * A config for a source the client just invented, so its Form can be built
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
     */
    private function form(string $elementType, array $source, array $values, bool $isNew): Form
    {
        return Form::make(array_values(array_filter(match ($source['type'] ?? ElementSources::TYPE_NATIVE) {
            ElementSources::TYPE_HEADING => $this->headingNodes(),
            ElementSources::TYPE_CUSTOM => $this->customNodes($elementType, $source, $values, $isNew),
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
     * @return list<Field|null>
     */
    private function nativeNodes(string $elementType, array $source, array $values): array
    {
        return [
            Field::make(t('Enabled'), Lightswitch::make('enabled')),
            $this->viewModeField($elementType, $source),
            ...$this->sortFields($elementType, $source, $values),
            $this->tableAttributesField($elementType, $source, false),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @return list<Field|null>
     */
    private function customNodes(string $elementType, array $source, array $values, bool $isNew): array
    {
        $condition = $elementType::createCondition();
        $siteOptions = $this->sites->getAllSites()
            ->map(fn (Site $site) => ['label' => t($site->name, category: 'site'), 'value' => $site->uid])
            ->values()
            ->all();
        $groupOptions = $this->userGroupOptions();

        return [
            Field::make(t('Label'), Text::make('label')),
            Field::make(
                t('{type} Criteria', ['type' => $elementType::displayName()]),
                ConditionBuilder::make('condition')
                    ->conditionClass($condition::class)
                    ->queryParams(['site', 'status'])
                    ->forProjectConfig()
                    ->addRuleLabel(t('Add a filter')),
            ),
            ...$this->sortFields($elementType, $source, $values),
            $this->tableAttributesField($elementType, $source, $isNew),
            $this->viewModeField($elementType, $source),
            count($siteOptions) > 1
                ? Field::make(t('Sites'), Choice::make('sites')->options($siteOptions)->allOption())
                    ->instructions(t('Choose which sites this source should be visible for.'))
                : null,
            $groupOptions !== []
                ? Field::make(t('User Groups'), Choice::make('userGroups')->options($groupOptions)->allOption())
                    ->instructions(t('Choose which user groups should have access to this source.'))
                : null,
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function viewModeField(string $elementType, array $source): Field
    {
        $options = array_map(fn (array $viewMode) => [
            // The icon is presentational, so the title becomes the button's
            // accessible name.
            'label' => $viewMode['title'],
            'icon' => $viewMode['icon'] ?? 'table',
            'value' => $viewMode['mode'],
        ], $this->viewModes($elementType, $source));

        return Field::make(
            t('Default View Mode'),
            Choice::make('defaultViewMode')->options($options)->presentation(ChoicePresentation::Buttons),
        );
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $values
     * @return list<Field>
     */
    private function sortFields(string $elementType, array $source, array $values): array
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
            Field::make(t('Default Sort'), Choice::make('defaultSort.attr')->options(array_map(
                fn (array $option) => ['label' => $option['label'], 'value' => $option['attr']],
                $options,
            )))->reactive()->width(75),
            Field::make(t('Sort direction'), $dir)->width(25),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function tableAttributesField(string $elementType, array $source, bool $isNew): ?Field
    {
        $options = $this->tableAttributeOptions($elementType, $source, $isNew);

        if ($options === []) {
            return null;
        }

        return Field::make(
            t('Default Table Columns'),
            Choice::make('tableAttributes')->options($options)->sortable(),
        )->instructions(t('Choose which table columns should be visible for this source by default.'));
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
    private function userGroupOptions(): array
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
            'tableAttributes' => $this->seedTableAttributes($elementType, $source),
        ];

        if (($source['type'] ?? null) !== ElementSources::TYPE_CUSTOM) {
            return ['enabled' => empty($source['disabled'])] + $shared;
        }

        return [
            'label' => $source['label'] ?? '',
            // Never seed an empty condition: the builder only re-reports its
            // value once someone edits it, so an untouched Form must post back
            // a config that already carries its class.
            'condition' => $source['condition'] ?? $elementType::createCondition()->getConfig(),
            'sites' => $this->seedScope($source, 'sites'),
            'userGroups' => $this->seedScope($source, 'userGroups'),
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
