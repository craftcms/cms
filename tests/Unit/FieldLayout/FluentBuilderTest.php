<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving;
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
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;

function fluentField(string $handle = 'body'): PlainText
{
    return new PlainText([
        'name' => ucfirst($handle),
        'handle' => $handle,
        'uid' => Str::uuid()->toString(),
    ]);
}

it('creates a layout for an element type', function () {
    expect(FieldLayout::create(Entry::class)->type)->toBe(Entry::class);
});

it('uses the translated default tab name', function () {
    Event::listen(NativeFieldsResolving::class, function (NativeFieldsResolving $event) {
        $event->fields[] = new TextField([
            'attribute' => 'title',
            'mandatory' => true,
        ]);
    });

    I18N::withLocale('de', null, function () {
        $layout = new FieldLayout;
        $name = FieldLayout::defaultTabName();
        $tab = $layout->getTab($name);

        $layout->tab($name, fn (FieldLayoutTab $tab) => $tab->add(new Heading('Metadata')));

        expect($name)->toBe('Inhalt')
            ->and($layout->getTabs())->toBe([$tab])
            ->and($tab->getElements())->toHaveCount(2);
    });
});

it('creates and reuses attached tabs', function () {
    $layout = new FieldLayout;
    $content = new Heading('Content');
    $metadata = new Heading('Metadata');

    expect($layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($content)))->toBe($layout);

    $tab = $layout->getTab(FieldLayout::defaultTabName());
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add($metadata));

    expect($tab->getLayout())->toBe($layout)
        ->and($layout->getTabs())->toBe([$tab])
        ->and($tab->getElements())->toBe([$content, $metadata]);
});

it('rejects unknown tab names', function () {
    expect(fn () => new FieldLayout()->getTab('Missing'))
        ->toThrow(InvalidArgumentException::class);
});

it('adds elements with back references and dates', function () {
    $layout = new FieldLayout;
    $element = CustomField::for(fluentField());
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
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::for($field)));
    $tab = $layout->getTab(FieldLayout::defaultTabName());
    $batchLayout = new FieldLayout;

    expect(fn () => $tab->add(CustomField::for($field)))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $batchLayout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::for($field), CustomField::for($field))))
        ->toThrow(InvalidArgumentException::class);
});

it('allows duplicate multi-instance fields', function () {
    $field = fluentField();
    $layout = new FieldLayout;
    $body = CustomField::for($field);
    $secondaryBody = CustomField::for($field)->handle('secondaryBody');

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(
        $body,
        $secondaryBody,
    ));

    expect($layout->getTab(FieldLayout::defaultTabName())->getElements())->toBe([$body, $secondaryBody]);
});

it('configures field elements fluently', function () {
    $userCondition = User::createCondition();
    $elementCondition = Entry::createCondition();
    $field = CustomField::for(fluentField())
        ->width(50)
        ->label('Teaser')
        ->instructions('Keep it short.')
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
    $field = CustomField::for(fluentField())
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
    $tip = new Tip('Careful')->dismissible()->warning();
    $markdown = new Markdown('Hello')->displayInPane(false);
    $template = new Template('_includes/card')->templateMode(TemplateMode::Cp);

    expect(new Heading('Metadata')->heading)->toBe('Metadata')
        ->and($tip->tip)->toBe('Careful')
        ->and($tip->dismissible)->toBeTrue()
        ->and($tip->style)->toBe(Tip::STYLE_WARNING)
        ->and($tip->warning(false)->style)->toBe(Tip::STYLE_TIP)
        ->and($markdown->content)->toBe('Hello')
        ->and($markdown->displayInPane)->toBeFalse()
        ->and($template->template)->toBe('_includes/card')
        ->and($template->templateMode)->toBe(TemplateMode::Cp->value)
        ->and($template->templateMode('site')->templateMode)->toBe('site')
        ->and(new HorizontalRule)->toBeInstanceOf(HorizontalRule::class)
        ->and(new LineBreak)->toBeInstanceOf(LineBreak::class);
});

it('invalidates field caches when fields are added and removed', function () {
    $layout = new FieldLayout;
    $field = fluentField();

    expect($layout->getCustomFields())->toBeEmpty();

    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::for($field)));

    expect($layout->getCustomFields())->toHaveCount(1);

    $layout->removeField($field);

    expect($layout->getCustomFields())->toBeEmpty();
});

it('removes a field from every tab', function () {
    $layout = new FieldLayout;
    $field = fluentField();
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::for($field)));
    $layout->tab('SEO', fn (FieldLayoutTab $tab) => $tab->add(CustomField::for($field)->handle('seoBody')));

    $layout->removeField($field);

    expect($layout->getAllElements())->toBeEmpty();
});

it('removes tabs and rehomes mandatory fields', function () {
    Event::listen(NativeFieldsResolving::class, function (NativeFieldsResolving $event) {
        $event->fields[] = new TextField([
            'attribute' => 'title',
            'mandatory' => true,
        ]);
    });

    $layout = new FieldLayout;
    $layout->tab('Legacy', fn (FieldLayoutTab $tab) => $tab->add(new Heading('Legacy')));

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
        CustomField::for(fluentField())->required(),
        new Heading('Metadata'),
    ));
    $config = $layout->getConfig();

    expect(FieldLayout::createFromConfig($config)->getConfig())->toBe($config);
});
