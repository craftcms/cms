<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Heading;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Sites;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class SectionEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly Section $section,
        private readonly Sites $sites,
        private readonly EntryTypes $entryTypesService,
        private readonly FormResolver $formResolver,
        public readonly bool $brandNew,
        private readonly bool $readOnly,
        public readonly bool $headlessMode,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
    {
        $values = $this->values ?? $this->initialValues();
        $type = $values['type'] instanceof SectionType
            ? $values['type']
            : SectionType::from((string) $values['type']);
        $handle = Handle::make('handle');

        if ($this->brandNew) {
            $handle->source('name');
        }

        $typeField = Field::make(
            t('Section Type'),
            Choice::make('type')->options(SectionType::asOptions()),
        )->instructions(t('What type of section is this?'));

        if ($this->section->id && $type !== SectionType::Single) {
            $typeField->warning(t('Changing this may result in data loss.'));
        }

        $form = Form::make([
            HiddenField::make('sectionId'),
            Field::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this section will be called in the control panel.'))
                ->required(),
            Field::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this section in the templates.'))
                ->required(),
            Field::make(
                t('Enable versioning for entries in this section'),
                Lightswitch::make('enableVersioning'),
            ),
            $typeField,
            Separator::make('entry-types-separator'),
            Heading::make('entry-types-heading', t('Entry Types'))
                ->description(t('Choose the types of entries that can be included in this section.')),
            Field::make(control: Choice::make('entryTypes')
                ->options($this->entryTypeOptions())
                ->multiple()),
            Separator::make('site-settings-separator'),
            Heading::make('site-settings-heading', t('Site settings'))
                ->description(t('Choose which sites this section should be available in, and configure the site-specific settings.')),
            Field::make(control: $this->siteSettingsControl()),
        ]);

        if ($this->isMultiSite() && in_array($type, [SectionType::Channel, SectionType::Structure], true)) {
            $propagation = Field::make(
                t('Propagation Method'),
                Choice::make('propagationMethod')->options(PropagationMethod::asOptions()),
            )->instructions(t('Of the enabled sites above, which sites should entries in this section be saved to?'));

            if ($this->section->id
                && $this->section->propagationMethod !== PropagationMethod::None
                && count($this->section->siteSettings) > 1) {
                $propagation->warning(t('Changing this may result in data loss.'));
            }

            $form->add($propagation);
        }

        if ($type === SectionType::Structure) {
            $form->add(
                Separator::make('structure-settings-separator'),
                Field::make(t('Max Levels'), Number::make('maxLevels')->min(1)->max(32767)->size(5))
                    ->instructions(t('The maximum number of levels this section can have.')),
                Field::make(
                    t('Default {type} Placement', ['type' => t('Entry')]),
                    Choice::make('defaultPlacement')->options(DefaultPlacement::asOptions()),
                )->instructions(t('Where new {type} should be placed by default in the structure.', [
                    'type' => t('entries'),
                ])),
            );
        }

        $form->add(
            Separator::make('preview-targets-separator'),
            Heading::make('preview-targets-heading', t('Preview Targets'))
                ->description(t('Locations that should be available for previewing entries in this section.')),
            Field::make(control: Table::make('previewTargets')
                ->columns([
                    'label' => ['heading' => t('Label'), 'type' => 'singleline'],
                    'urlFormat' => [
                        'heading' => t('URL Format'),
                        'type' => 'singleline',
                        'textExpanderTriggers' => SelectOptions::getEnvTextExpanderTriggers(true),
                    ],
                    'refresh' => ['heading' => t('Auto-Refresh'), 'type' => 'lightswitch'],
                ])
                ->allowAdd()
                ->allowDelete()),
        );

        if (in_array($type, [SectionType::Channel, SectionType::Structure], true)) {
            $form->add(
                Separator::make('author-settings-separator'),
                Field::make(t('Min Authors'), Number::make('minAuthors')->min(0)->max(32767)->size(5))
                    ->instructions(t('The minimum number of authors that entries in this section can have.')),
                Field::make(t('Max Authors'), Number::make('maxAuthors')->min(0)->max(32767)->size(5))
                    ->instructions(t('The maximum number of authors that entries in this section can have.')),
            );
        }

        return $this->formResolver->resolve($form, new FormContext(
            values: $values,
            errors: $this->section->errors()->getMessages(),
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([SectionsController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([SectionsController::class, 'renderForm']);
    }

    /** @return Collection<int, EntryType> */
    public function entryTypes(): Collection
    {
        return $this->entryTypesService->getAllEntryTypes();
    }

    public function homepageUri(): string
    {
        return Element::HOMEPAGE_URI;
    }

    /** @return list<array<string, mixed>> */
    public function templateOptions(): array
    {
        return SelectOptions::getTemplateSuggestions();
    }

    public function isMultiSite(): bool
    {
        return $this->sites->isMultiSite();
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        $siteSettings = [];

        foreach ($this->sites->getAllSites() as $site) {
            $settings = $this->section->siteSettings[$site->id] ?? null;
            $uriFormat = $settings === null ? '' : ($settings->uriFormat ?? '');
            $siteSettings[$site->handle] = [
                'enabled' => $this->brandNew || $settings !== null,
                'siteId' => $site->id,
                'name' => $site->getName(),
                'singleHomepage' => false,
                'singleUri' => $uriFormat,
                'uriFormat' => $uriFormat,
                'template' => $settings === null ? '' : ($settings->template ?? ''),
                'enabledByDefault' => $settings === null || $settings->enabledByDefault,
            ];
        }

        return [
            'sectionId' => $this->section->id,
            'name' => $this->section->name ?? '',
            'handle' => $this->section->handle ?? '',
            'type' => $this->section->type ?? SectionType::Channel,
            'entryTypes' => array_map(
                fn ($entryType): int => (int) $entryType->id,
                $this->section->entryTypes,
            ),
            'enableVersioning' => $this->section->enableVersioning,
            'minAuthors' => $this->section->minAuthors,
            'maxAuthors' => $this->section->maxAuthors ?? '',
            'maxLevels' => $this->section->maxLevels ?? '',
            'propagationMethod' => $this->section->propagationMethod,
            'defaultPlacement' => $this->section->defaultPlacement,
            'previewTargets' => $this->section->previewTargets ?? [],
            'sites' => $siteSettings,
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function entryTypeOptions(): array
    {
        return $this->entryTypesService->getAllEntryTypes()
            ->map(fn ($entryType): array => [
                'label' => $entryType->name,
                'value' => (int) $entryType->id,
            ])
            ->values()
            ->all();
    }

    private function siteSettingsControl(): Table
    {
        return Table::make('sites')
            ->keyed()
            ->columns([
                'name' => ['heading' => t('Site'), 'type' => 'heading'],
                'enabled' => ['heading' => t('Enabled'), 'type' => 'lightswitch'],
                'singleHomepage' => ['heading' => t('Homepage'), 'type' => 'checkbox'],
                'singleUri' => ['heading' => t('URI'), 'type' => 'singleline'],
                'uriFormat' => ['heading' => t('Entry URI Format'), 'type' => 'singleline'],
                'template' => [
                    'heading' => t('Template'),
                    'type' => 'template',
                    'options' => $this->templateOptions(),
                ],
                'enabledByDefault' => ['heading' => t('Default Status'), 'type' => 'lightswitch'],
            ]);
    }
}
