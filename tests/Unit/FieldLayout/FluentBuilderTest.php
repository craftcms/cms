<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Template;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;

function fluentField(string $handle = 'body'): PlainText
{
    return new PlainText([
        'name' => ucfirst($handle),
        'handle' => $handle,
        'uid' => Str::uuid()->toString(),
    ]);
}

it('creates a layout for an element type', function () {
    expect(FieldLayout::make(Entry::class)->type)->toBe(Entry::class);
});

it('uses the translated default tab name', function () {
    app(NativeFields::class)->register('translated-default-tab-test', function (FieldLayout $fieldLayout, array $fields) {
        $fields[] = new TextField([
            'attribute' => 'title',
            'mandatory' => true,
        ]);

        return $fields;
    });

    I18N::withLocale('de', null, function () {
        $layout = new FieldLayout;
        $name = FieldLayout::defaultTabName();
        $tab = $layout->getTab($name);

        $layout->tab($name, fn (FieldLayoutTab $tab) => $tab->add(Heading::make('Metadata')));

        expect($name)->toBe('Inhalt')
            ->and($layout->getTabs())->toBe([$tab])
            ->and($tab->getElements())->toHaveCount(2);
    });
});

it('creates and reuses attached tabs', function () {
    $layout = new FieldLayout;
    $content = Heading::make('Content');
    $metadata = Heading::make('Metadata');

    expect($layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($content)))->toBe($layout);

    $tab = $layout->getTab(FieldLayout::defaultTabName());
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($metadata));

    expect($tab->getLayout())->toBe($layout)
        ->and($layout->getTabs())->toBe([$tab])
        ->and($tab->getElements())->toBe([$content, $metadata]);
});

it('creates tabs without a configuration callback', function () {
    $layout = new FieldLayout;

    expect($layout->tab('SEO'))->toBe($layout)
        ->and($layout->getTab('SEO')->name)->toBe('SEO');
});

it('rejects unknown tab names', function () {
    expect(fn () => new FieldLayout()->getTab('Missing'))
        ->toThrow(InvalidArgumentException::class);
});

it('adds elements with back references and dates', function () {
    $layout = new FieldLayout;
    $element = CustomField::make(fluentField());
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($element));
    $tab = $layout->getTab(FieldLayout::defaultTabName());

    expect($tab->getElements())->toBe([$element])
        ->and($element->getLayout())->toBe($layout)
        ->and($element->dateAdded)->not()->toBeNull();
});

it('rejects duplicate single-instance fields including within one batch', function () {
    $field = new class(['name' => 'Computed', 'handle' => 'computed', 'uid' => Str::uuid()->toString()]) extends PlainText
    {
        #[Override]
        public static function dbType(): null
        {
            return null;
        }
    };
    $layout = new FieldLayout;
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)));
    $tab = $layout->getTab(FieldLayout::defaultTabName());
    $batchLayout = new FieldLayout;

    expect(fn () => $tab->add(CustomField::make($field)))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $batchLayout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field), CustomField::make($field))))
        ->toThrow(InvalidArgumentException::class);
});

it('allows duplicate multi-instance fields', function () {
    $field = fluentField();
    $layout = new FieldLayout;
    $body = CustomField::make($field);
    $secondaryBody = CustomField::make($field)->handle('secondaryBody');

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(
        $body,
        $secondaryBody,
    ));

    expect($layout->getTab(FieldLayout::defaultTabName())->getElements())->toBe([$body, $secondaryBody]);
});

it('configures field elements fluently', function () {
    $userCondition = User::createCondition();
    $elementCondition = Entry::createCondition();
    $field = CustomField::make(fluentField())
        ->width(50)
        ->label('Teaser')
        ->instructions('Keep it short.')
        ->instructionsPosition('after')
        ->tip('Optional tip')
        ->warning('Required warning')
        ->required(false)
        ->userCondition($userCondition)
        ->elementCondition($elementCondition)
        ->editCondition($userCondition)
        ->elementEditCondition($elementCondition);

    expect($field->width)->toBe(50)
        ->and($field->label())->toBe('Teaser')
        ->and($field->instructions)->toBe('Keep it short.')
        ->and($field->instructionsPosition)->toBe('after')
        ->and($field->tip)->toBe('Optional tip')
        ->and($field->warning)->toBe('Required warning')
        ->and($field->required)->toBeFalse()
        ->and($field->getUserCondition())->toBe($userCondition)
        ->and($field->getElementCondition())->toBe($elementCondition)
        ->and($field->getEditCondition())->toBe($userCondition)
        ->and($field->getElementEditCondition())->toBe($elementCondition);
});

it('keeps custom field overrides synchronized', function () {
    $layout = new FieldLayout;
    $field = CustomField::make(fluentField())
        ->handle('teaser')
        ->label('Teaser')
        ->instructions('Short copy');

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($field));

    expect($field->attribute())->toBe('teaser')
        ->and($field->getField()->name)->toBe('Teaser')
        ->and($field->getField()->instructions)->toBe('Short copy')
        ->and($layout->getFieldByHandle('teaser'))->toBe($field->getField());

    $field->handle(null)->labelHidden(false)->instructions(null);

    expect($field->attribute())->toBe('body')
        ->and($field->label())->toBe('Body')
        ->and($field->getField()->instructions)->toBeNull();
});

it('builds UI elements fluently', function () {
    $tip = Tip::make('Careful')->dismissible()->warning();
    $markdown = Markdown::make('Hello')->displayInPane(false);
    $template = Template::make('_includes/card')->templateMode(TemplateMode::Cp);

    expect(Heading::make('Metadata')->heading)->toBe('Metadata')
        ->and($tip->tip)->toBe('Careful')
        ->and($tip->dismissible)->toBeTrue()
        ->and($tip->style)->toBe(Tip::STYLE_WARNING)
        ->and($tip->warning(false)->style)->toBe(Tip::STYLE_TIP)
        ->and($markdown->content)->toBe('Hello')
        ->and($markdown->displayInPane)->toBeFalse()
        ->and($template->template)->toBe('_includes/card')
        ->and($template->templateMode)->toBe(TemplateMode::Cp->value)
        ->and($template->templateMode('site')->templateMode)->toBe('site')
        ->and(HorizontalRule::make())->toBeInstanceOf(HorizontalRule::class)
        ->and(LineBreak::make())->toBeInstanceOf(LineBreak::class);
});

it('configures persisted layout options fluently', function () {
    $layout = new FieldLayout;

    $layout
        ->generatedFields([[
            'uid' => 'generated-field',
            'name' => 'Summary',
        ]])
        ->cardView(['generatedField:generated-field'])
        ->thumbFieldKey('layoutElement:thumbnail')
        ->cardThumbAlignment('start');

    expect($layout->getGeneratedFields())->toBe([[
        'uid' => 'generated-field',
        'name' => 'Summary',
    ]])
        ->and($layout->getCardView())->toBe(['generatedField:generated-field'])
        ->and($layout->thumbFieldKey)->toBe('layoutElement:thumbnail')
        ->and($layout->getCardThumbAlignment())->toBe('start');
});

it('rejects invalid constrained fluent values', function () {
    expect(fn () => new TextField(['attribute' => 'title'])->instructionsPosition('middle'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new FieldLayout()->cardThumbAlignment('middle'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Template::make('_includes/card')->templateMode('invalid'))
        ->toThrow(ValueError::class);
});

it('adds elements with tab helpers', function () {
    $layout = new FieldLayout;

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab
        ->field(fluentField(), fn (CustomField $field) => $field->required())
        ->heading('Metadata')
        ->tip('Keep this concise.', fn (Tip $tip) => $tip->dismissible())
        ->warning('This is required.')
        ->markdown('## Details')
        ->template('_includes/card')
        ->horizontalRule()
        ->lineBreak());

    $elements = $layout
        ->getTab(FieldLayout::defaultTabName())
        ->getElements();

    expect($elements)->toHaveCount(8)
        ->and(array_any($elements, fn ($element) => $element instanceof CustomField && $element->required))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof Heading && $element->heading === 'Metadata'))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof Tip && $element->dismissible))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof Tip && $element->style === Tip::STYLE_WARNING))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof Markdown))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof Template))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof HorizontalRule))->toBeTrue()
        ->and(array_any($elements, fn ($element) => $element instanceof LineBreak))->toBeTrue();
});

it('invalidates field caches when fields are added and removed', function () {
    $layout = new FieldLayout;
    $field = fluentField();

    expect($layout->getCustomFields())->toBeEmpty();

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)));

    expect($layout->getCustomFields())->toHaveCount(1);

    $layout->removeField($field);

    expect($layout->getCustomFields())->toBeEmpty();
});

it('removes a field from every tab', function () {
    $layout = new FieldLayout;
    $field = fluentField();
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)));
    $layout->tab('SEO', fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)->handle('seoBody')));

    $layout->removeField($field);

    expect($layout->getAllElements())->toBeEmpty();
});

it('removes tabs and rehomes mandatory fields', function () {
    app(NativeFields::class)->register('rehome-mandatory-fields-test', function (FieldLayout $fieldLayout, array $fields) {
        $fields[] = new TextField([
            'attribute' => 'title',
            'mandatory' => true,
        ]);

        return $fields;
    });

    $layout = new FieldLayout;
    $layout->tab('Legacy', fn (FieldLayoutTab $tab) => $tab->add(Heading::make('Legacy')));

    $layout->removeTab(FieldLayout::defaultTabName());

    $mandatoryField = array_find(
        $layout->getTab('Legacy')->getElements(),
        fn (FieldLayoutElement $element) => $element instanceof TextField && $element->attribute() === 'title',
    );

    expect($layout->getTabs())->toHaveCount(1)
        ->and($mandatoryField)->toBeInstanceOf(TextField::class);
});

it('round trips config without changing generated uids', function () {
    $layout = new FieldLayout;
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(
        CustomField::make(fluentField())->required()->instructionsPosition('after'),
        Heading::make('Metadata'),
    ));
    $config = $layout->getConfig();

    expect(FieldLayout::createFromConfig($config)->getConfig())->toBe($config);
});
