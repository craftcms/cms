<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Date;
use CraftCms\Cms\Form\Controls\DateTime;
use CraftCms\Cms\Form\Controls\Money;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Range;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Controls\Time;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Support\Facades\I18N;
use Symfony\Component\DomCrawler\Crawler;

function scalarControlsForm(): Form
{
    return Form::make([
        Field::make('Summary',
            Textarea::make('summary')->rows(4)->maxLength(120)->placeholder('<write>'),
        ),
        Field::make('Retry duration', Text::make('retryDuration')
            ->inputMode('numeric')
            ->maxLength(4)
            ->autofocus()
            ->autocomplete('one-time-code')
            ->autocorrect(false)
            ->autocapitalize(false)
            ->size(6)
            ->dir('rtl'),
        ),
        Field::make('Choice',
            Choice::make('choice')->options([
                ['label' => '<None>', 'value' => ''],
                ['label' => 'One', 'value' => 'one'],
                ['label' => 'Enabled', 'value' => true],
            ]),
        )->required(),
        Field::make('Tags',
            Choice::make('tags')->multiple()->options([
                ['label' => 'Alpha', 'value' => 'a'],
                ['label' => 'Beta', 'value' => 'b'],
            ]),
        )->required(),
        Field::make('Number', Number::make('number')->min(0)->max(10)->step(0.5)),
        Field::make('Range', Range::make('range')->min(1)->max(5)->step(1)),
        Field::make('Date', Date::make('date')->min('2026-01-01')->max('2026-12-31')),
        Field::make('Date and time',
            DateTime::make('datetime')->showTime()->showTimeZone()->minuteIncrement(15),
        ),
        Field::make('Time', Time::make('time')->step(60)),
        Field::make('Color', Color::make('color')->presets(['#ff0000'])),
        Field::make('Money',
            Money::make('price')->currency('EUR')->locale('nl_BE')->min(0),
        ),
    ]);
}

function scalarControlsCrawler(ControlMode $mode = ControlMode::Editable): Crawler
{
    $payload = app(FormResolver::class)->resolve(scalarControlsForm(), new FormContext(
        namespace: 'settings',
        values: ['settings' => [
            'summary' => '<script>alert(1)</script>',
            'retryDuration' => '60',
            'choice' => true,
            'tags' => [],
            'number' => '',
            'range' => 3,
            'date' => '2026-08-04',
            'datetime' => ['date' => '2026-08-04', 'time' => '14:30', 'timezone' => 'Europe/Brussels'],
            'time' => '14:30',
            'color' => 'ff0000',
            'price' => ['value' => '12,50', 'locale' => 'nl_BE'],
        ]],
        errors: ['number' => ['Enter a number.']],
        mode: $mode,
    ));

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

it('resolves and renders scalar and choice Controls with canonical values', function () {
    $crawler = scalarControlsCrawler();

    expect($crawler->filter('textarea[name="settings[summary]"]')->text())->toBe('<script>alert(1)</script>')
        ->and($crawler->filter('textarea[rows="4"][maxlength="120"][placeholder="<write>"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input[inputmode="numeric"] input[name="settings[retryDuration]"][inputmode="numeric"][maxlength="4"][autocomplete="one-time-code"][autocorrect="off"][autocapitalize="none"][size="6"][dir="rtl"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-select select[name="settings[choice]"][required] option[value="1"][selected]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][name="settings[tags]"][value=""]'))->toHaveCount(1)
        ->and($crawler->filter('craft-checkbox-group craft-checkbox input[type="checkbox"][name="settings[tags][]"]'))->toHaveCount(2)
        ->and($crawler->filter('[role="group"][aria-required="true"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="number"][name="settings[number]"][value=""][min="0"][max="10"][step="0.5"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="number"][aria-invalid="true"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="range"][value="3"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="date"][value="2026-08-04"][min="2026-01-01"][max="2026-12-31"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-date-time input[type="date"][name="settings[datetime][date]"][value="2026-08-04"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-date-time input[type="time"][name="settings[datetime][time]"][value="14:30"][step="900"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[datetime][timezone]"][value="Europe/Brussels"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-date-time input[type="hidden"][name="settings[datetime][locale]"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="time"][value="14:30"][step="60"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-color input[value="ff0000"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-money input[name="settings[price][value]"][value="12,50"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][name="settings[price][locale]"][value="nl_BE"]'))->toHaveCount(1)
        ->and($crawler->html())->not->toContain('<script>');
});

it('uses the current formatting locale when money does not declare one', function () {
    expect(Money::make('price')->props()['locale'])->toBe(I18N::getFormattingLocale()->id);
});

it('serializes choice presentations', function () {
    expect(Choice::make('choice')->presentation(ChoicePresentation::Buttons)->props()['presentation'])->toBe('buttons')
        ->and(Choice::make('choice')->multiple()->props()['presentation'])->toBe('checkboxes');
});

it('serializes text input behavior', function () {
    expect(Text::make('name')
        ->autofocus()
        ->autocomplete(false)
        ->autocorrect(false)
        ->autocapitalize(false)
        ->size(12)
        ->dir('rtl')
        ->props())->toMatchArray([
            'autofocus' => true,
            'autocomplete' => false,
            'autocorrect' => false,
            'autocapitalize' => false,
            'size' => 12,
            'dir' => 'rtl',
        ]);
});

it('serializes field notice markdown as HTML', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([
            Field::make('Name', Text::make('name'))
                ->tip('Read the [docs](https://craftcms.com).')
                ->warning('Use **care**.'),
        ]),
        new FormContext,
    );

    $tip = new Crawler($payload->nodes[0]->props['tipHtml']);
    $warning = new Crawler($payload->nodes[0]->props['warningHtml']);

    expect($tip->filter('a')->text())->toBe('docs')
        ->and($tip->filter('a')->attr('href'))->toBe('https://craftcms.com')
        ->and($warning->filter('strong')->text())->toBe('care');
});

it('resolves and renders hidden values', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([HiddenField::make('siteId')]),
        new FormContext(values: ['siteId' => 42]),
    );
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($payload->values)->toBe(['siteId' => 42])
        ->and($crawler->filter('input[type="hidden"][name="siteId"][value="42"]'))->toHaveCount(1);
});

it('renders combobox options through the web component', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make()->control(Combobox::make('path')
            ->limit(10)
            ->clearable()
            ->requireOptionMatch()
            ->showAllOnEmpty()
            ->dir('rtl')
            ->options([
                ['type' => 'optgroup', 'label' => 'Aliases', 'options' => [
                    ['label' => '<Root>', 'value' => '@root'],
                ]],
            ]))]),
        new FormContext(namespace: 'settings', values: ['settings' => ['path' => '@root']]),
    );
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    $combobox = $crawler->filter('craft-combobox[name="settings[path]"][model-value="@root"]');

    expect($combobox)->toHaveCount(1)
        ->and($combobox->attr('limit'))->toBe('10')
        ->and($combobox->attr('clearable'))->not->toBeNull()
        ->and($combobox->attr('requireoptionmatch'))->not->toBeNull()
        ->and($combobox->attr('show-all-on-empty'))->not->toBeNull()
        ->and($combobox->attr('dir'))->toBe('rtl')
        ->and(json_decode((string) $combobox->attr('options'), true))->toBe([
            ['type' => 'optgroup', 'label' => 'Aliases', 'options' => [
                ['label' => '<Root>', 'value' => '@root'],
            ]],
        ]);
});

it('serializes and validates combobox behavior', function () {
    expect(Combobox::make('path')
        ->limit(25)
        ->clearable()
        ->requireOptionMatch()
        ->showAllOnEmpty()
        ->dir('rtl')
        ->props())->toMatchArray([
            'limit' => 25,
            'clearable' => true,
            'requireOptionMatch' => true,
            'showAllOnEmpty' => true,
            'dir' => 'rtl',
        ])
        ->and(fn () => Combobox::make('path')->limit(0))->toThrow(InvalidArgumentException::class);
});

it('renders choice presentations through CP components', function (ChoicePresentation $presentation, bool $multiple, string $group, string $option) {
    $choice = Choice::make('choice')
        ->options([
            ['label' => 'One', 'value' => 'one'],
            ['label' => 'Two', 'value' => 'two'],
        ])
        ->multiple($multiple)
        ->presentation($presentation);
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make()->control($choice)]),
        new FormContext(namespace: 'settings', values: ['settings' => ['choice' => $multiple ? ['one'] : 'one']]),
    );
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter($group))->toHaveCount(1)
        ->and($crawler->filter($option))->toHaveCount(2);
})->with([
    'select' => [ChoicePresentation::Select, false, 'craft-select', 'craft-select option'],
    'checkboxes' => [ChoicePresentation::Checkboxes, true, 'craft-checkbox-group', 'craft-checkbox'],
    'radios' => [ChoicePresentation::Radios, false, 'craft-radio-group', 'craft-radio'],
    'buttons' => [ChoicePresentation::Buttons, false, 'craft-button-group', 'craft-button'],
    'multiple buttons' => [ChoicePresentation::Buttons, true, 'craft-button-group[multiple]', 'craft-button'],
]);

it('uses scalar and choice Controls in built-in field settings', function () {
    $payload = app(FormResolver::class)->resolve(new PlainText()->settingsForm(), new FormContext(namespace: 'settings'));
    $components = collect($payload->nodes)
        ->flatMap(fn ($node) => $node->children === null ? [$node] : [$node, ...$node->children])
        ->map(fn ($node) => $node->control?->component)
        ->filter();

    expect($components)->toContain('craft:choice', 'craft:number');
});

it('displays scalar and choice values without submitting them in non-editable modes', function (ControlMode $mode) {
    $crawler = scalarControlsCrawler($mode);

    expect($crawler->filter('[name]'))->toHaveCount(0)
        ->and($crawler->filter('textarea')->text())->toBe('<script>alert(1)</script>')
        ->and($crawler->filter('select option[value="1"][selected]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="range"][value="3"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="date"][value="2026-08-04"]'))->toHaveCount(2)
        ->and($crawler->filter('input[value="Europe/Brussels"]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="time"][value="14:30"]'))->toHaveCount(2)
        ->and($crawler->filter('craft-input-color input[value="ff0000"]'))->toHaveCount(1)
        ->and($crawler->filter('input[value="12,50"]'))->toHaveCount(1);
})->with([
    'read-only' => ControlMode::ReadOnly,
    'disabled' => ControlMode::Disabled,
]);
