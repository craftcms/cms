<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\IconPicker;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Support\Facades\Sites;

use function CraftCms\Cms\t;

class EntryTypeEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly EntryType $entryType,
        private readonly bool $brandNew,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
    {
        $values = $this->values ?? $this->initialValues();
        $handle = Handle::make('handle');
        $objectTemplateTriggers = SelectOptions::getObjectTemplateTextExpanderTriggers(
            Entry::class,
            [$this->entryType->getFieldLayout()],
        );
        $objectTemplateTip = SelectOptions::getObjectTemplateTip();

        if ($this->brandNew && empty($values['handle'])) {
            $handle->source('name');
        }

        $form = Form::make([
            HiddenField::make('entryTypeId'),
            Field::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this {type} will be called in the control panel.', ['type' => Entry::lowerDisplayName()]))
                ->required(),
            Field::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this {type} in the templates.', ['type' => Entry::lowerDisplayName()]))
                ->required(),
            Field::make(t('Description'), Textarea::make('description')),
            Field::make(t('Icon'), IconPicker::make('icon')),
            Field::make(t('Color'), Color::make('color')),
            Field::make(t('UI Label Format'), Text::make('uiLabelFormat')
                ->monospace()
                ->textExpanderTriggers($objectTemplateTriggers))
                ->instructions(t('How {type} of this type should be labeled in the control panel.', ['type' => Entry::lowerDisplayName()]))
                ->tip($objectTemplateTip),
        ]);

        if (Sites::isMultiSite()) {
            $form->add(
                Field::make(
                    t('Title Translation Method'),
                    Choice::make('titleTranslationMethod')->options(TranslationMethod::asOptions())->reactive(),
                )->instructions(t('How should the title be translated?')),
            );

            if (($values['titleTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $form->add(Field::make(
                    t('Title Translation Key Format'),
                    Text::make('titleTranslationKeyFormat')
                        ->monospace()
                        ->textExpanderTriggers($objectTemplateTriggers),
                )->tip($objectTemplateTip));
            }
        }

        $form->add(
            Field::make(t('Default Title Format'), Text::make('titleFormat')
                ->monospace()
                ->textExpanderTriggers($objectTemplateTriggers))
                ->instructions(t('The format that {type} titles should take when generated.', ['type' => Entry::lowerDisplayName()]))
                ->tip($objectTemplateTip),
            Field::make(t('Allow line breaks in titles'), Lightswitch::make('allowLineBreaksInTitles')),
            Field::make(t('Show the Slug field'), Lightswitch::make('showSlugField')->reactive()),
        );

        if (Sites::isMultiSite() && ($values['showSlugField'] ?? false)) {
            $form->add(
                Field::make(
                    t('Slug Translation Method'),
                    Choice::make('slugTranslationMethod')->options(TranslationMethod::asOptions())->reactive(),
                )->instructions(t('How should slugs be translated?')),
            );

            if (($values['slugTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $form->add(Field::make(
                    t('Slug Translation Key Format'),
                    Text::make('slugTranslationKeyFormat')
                        ->monospace()
                        ->textExpanderTriggers($objectTemplateTriggers),
                )->tip($objectTemplateTip));
            }
        }

        $form->add(
            Field::make(t('Show the Status field'), Lightswitch::make('showStatusField')),
            Separator::make('field-layout-separator'),
            Field::make(null, FieldLayoutDesigner::make('fieldLayout')
                ->elementType(Entry::class)
                ->withGeneratedFields()
                ->withCardViewDesigner()),
        );

        return app(FormResolver::class)->resolve($form, new FormContext(
            values: $values,
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([EntryTypesController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([EntryTypesController::class, 'renderForm']);
    }

    public function brandNew(): bool
    {
        return $this->brandNew;
    }

    public function lowerTypeName(): string
    {
        return Entry::lowerDisplayName();
    }

    public function metadataHtml(): ?string
    {
        return $this->entryType->id
            ? app(ContentHtml::class)->metadataHtml($this->entryType->getMetadata())
            : null;
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        $fieldLayout = $this->entryType->getFieldLayout();

        if ($this->entryType->hasTitleField && ! $fieldLayout->isFieldIncluded('title')) {
            $fieldLayout->prependElements([new EntryTitleField]);
        } elseif (! $this->entryType->hasTitleField) {
            foreach ($fieldLayout->getTabs() as $tab) {
                $tab->setElements(array_values(array_filter(
                    $tab->getElements(),
                    fn (FieldLayoutElement $element): bool => ! $element instanceof EntryTitleField,
                )));
            }
        }

        return [
            'entryTypeId' => $this->entryType->id,
            'name' => $this->entryType->name ?? '',
            'handle' => $this->entryType->handle ?? '',
            'description' => $this->entryType->description ?? '',
            'icon' => $this->entryType->icon ?? '',
            'color' => $this->entryType->color->value ?? '',
            'uiLabelFormat' => $this->entryType->uiLabelFormat,
            'titleTranslationMethod' => $this->entryType->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $this->entryType->titleTranslationKeyFormat ?? '',
            'titleFormat' => $this->entryType->titleFormat ?? '',
            'allowLineBreaksInTitles' => (bool) $this->entryType->allowLineBreaksInTitles,
            'showSlugField' => (bool) $this->entryType->showSlugField,
            'slugTranslationMethod' => $this->entryType->slugTranslationMethod->value,
            'slugTranslationKeyFormat' => $this->entryType->slugTranslationKeyFormat ?? '',
            'showStatusField' => (bool) $this->entryType->showStatusField,
            'fieldLayout' => [
                'id' => $fieldLayout->id,
                'uid' => $fieldLayout->uid,
                ...($fieldLayout->getConfig() ?? []),
            ],
        ];
    }
}
