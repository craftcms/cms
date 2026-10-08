<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Address;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Link;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function compositeControlsUi(): Ui
{
    return Ui::make([
        Field::make('Body',
            Markdown::make('body')
                ->rows(6)
                ->placeholder('Write <Markdown>')
                ->toolbarButtons(['bold', 'link'])
                ->showStats()
                ->textExpanderTriggers([
                    ['trigger' => '@', 'boundary' => 'whitespace', 'label' => 'People', 'options' => [
                        ['label' => 'Ada Lovelace', 'value' => '@ada'],
                    ]],
                ]),
        ),
        Field::make('Rows',
            Table::make('rows')
                ->columns([
                    'name' => ['heading' => 'Name', 'type' => 'singleline'],
                    'enabled' => ['heading' => 'Enabled', 'type' => 'checkbox'],
                ])
                ->allowAdd()
                ->allowDelete(),
        ),
        Field::make('Link',
            Link::make('link')
                ->types([
                    ['id' => 'url', 'label' => 'URL', 'kind' => 'text'],
                    ['id' => 'email', 'label' => 'Email', 'kind' => 'text'],
                ])
                ->showLabelField()
                ->advancedFields(['title']),
        ),
        Field::make('Address',
            Address::make('address')->countryCode('US'),
        ),
        Field::make('Icon', IconPicker::make('icon')->freeOnly()),
    ]);
}

function compositeControlsCrawler(ControlMode $mode = ControlMode::Editable): Crawler
{
    $payload = app(UiResolver::class)->resolve(compositeControlsUi(), new UiContext(
        namespace: 'settings',
        values: ['settings' => [
            'body' => '<script>alert(1)</script> **Safe**',
            'rows' => [['name' => '<Row>', 'enabled' => true]],
            'link' => ['type' => 'url', 'value' => 'https://craftcms.com', 'label' => '<Craft>', 'title' => 'Docs'],
            'address' => [
                'addressLine1' => '123 Main Street',
                'administrativeArea' => 'CA',
                'locality' => 'Los Angeles',
                'postalCode' => '90001',
            ],
            'icon' => 'star',
        ]],
        errors: [
            'link.value' => ['Enter a valid link.'],
            'address' => ['Choose an address.'],
            'unmatched' => ['Unmatched error.'],
        ],
        mode: $mode,
    ));

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('resolves documented composite Control shapes and properties', function () {
    $payload = app(UiResolver::class)->resolve(compositeControlsUi(), new UiContext(namespace: 'settings'));
    $controls = collect($payload->nodes)->mapWithKeys(fn ($node) => [
        $node->control->component => $node->control,
    ]);

    expect($controls->keys()->all())->toBe([
        'craft:markdown',
        'craft:table',
        'craft:link',
        'craft:address',
        'craft:icon-picker',
    ])->and($controls['craft:markdown']->props)->toMatchArray([
        'rows' => 6,
        'placeholder' => 'Write <Markdown>',
        'toolbarButtons' => ['bold', 'link'],
        'showStats' => true,
        'textExpanderTriggers' => [
            ['trigger' => '@', 'boundary' => 'whitespace', 'label' => 'People', 'options' => [
                ['label' => 'Ada Lovelace', 'value' => '@ada'],
            ]],
        ],
    ])->and($controls['craft:table']->props['columns']['name'])->toBe([
        'heading' => 'Name',
        'type' => 'singleline',
    ])->and($controls['craft:link']->props['types'][0])->toBe([
        'id' => 'url',
        'label' => 'URL',
        'kind' => 'text',
    ])->and($controls['craft:address']->props['countryCode'])->toBe('US')
        ->and(collect($controls['craft:address']->props['fields'])->keyBy('name')['administrativeArea'])->toMatchArray([
            'type' => 'select',
            'visible' => true,
        ])->and($controls['craft:icon-picker']->props)->toBe(['freeOnly' => true]);
});

it('renders composite Controls with nested submission names and escaped values', function () {
    $crawler = compositeControlsCrawler();
    $markdown = $crawler->filter('craft-markdown-field[name="settings[body]"][sanitize-html][show-stats]');
    $textExpander = $crawler->filter('craft-text-expander');

    expect($markdown)->toHaveCount(1)
        ->and($textExpander)->toHaveCount(1)
        ->and($textExpander->attr('for'))->toBe($markdown->attr('id'))
        ->and($textExpander->attr('slot'))->toBe('input')
        ->and(json_decode($crawler->filter('craft-table-ui')->attr('data-payload'), true)['values']['settings']['rows'])->toBe([['name' => '<Row>', 'enabled' => true]])
        ->and($crawler->filter('craft-link-field[name="settings[link]"][model-value]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[address][addressLine1]"][value="123 Main Street"]'))->toHaveCount(1)
        ->and($crawler->filter('select[name="settings[address][administrativeArea]"] option[value="CA"][selected]'))->toHaveCount(1)
        ->and($crawler->filter('craft-icon-picker[name="settings[icon]"][value="star"][free-only]'))->toHaveCount(1)
        ->and($crawler->html())->toContain('Enter a valid link.', 'Choose an address.', 'Unmatched error.')
        ->and($crawler->html())->not->toContain('<script>');
});

it('displays composite values without submitting them in non-editable modes', function (ControlMode $mode) {
    $crawler = compositeControlsCrawler($mode);

    expect($crawler->filter('input:not([disabled])[name], textarea:not([disabled])[name], select:not([disabled])[name]')->each(fn (Crawler $node) => $node->outerHtml()))->toBe([])
        ->and($crawler->filter('craft-markdown-field')->text())->toContain('**Safe**')
        ->and(json_decode($crawler->filter('craft-table-ui')->attr('data-payload'), true)['nodes'][0]['control']['mode'])->toBe($mode->value)
        ->and($crawler->filter('craft-link-field[disabled][model-value]'))->toHaveCount(1)
        ->and($crawler->filter('input[value="123 Main Street"][disabled]'))->toHaveCount(1)
        ->and($crawler->filter('craft-icon-picker[disabled][value="star"]'))->toHaveCount(1);
})->with([
    'read-only' => ControlMode::ReadOnly,
    'disabled' => ControlMode::Disabled,
]);

it('preserves keyed Table rows in payloads and PHP submission names', function () {
    $ui = Ui::make([
        Field::make()->control(Table::make('rows')
            ->keyed()
            ->columns(['name' => ['heading' => 'Name', 'type' => 'singleline']])
            ->value(['site-one' => ['name' => 'Primary']])),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($payload->values['settings']['rows'])->toBe(['site-one' => ['name' => 'Primary']])
        ->and(json_decode($crawler->filter('craft-table-ui')->attr('data-payload'), true)['values']['settings']['rows'])->toBe(['site-one' => ['name' => 'Primary']]);
});

it('passes a custom add row label through Table payloads and HTML', function () {
    $form = Ui::make([
        Field::make()->control(Table::make('rows')
            ->columns(['name' => ['heading' => 'Name', 'type' => 'singleline']])
            ->allowAdd()
            ->addRowLabel('Add a coupon')),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($payload->nodes[0]->control->props['addRowLabel'])->toBe('Add a coupon')
        ->and($crawler->filter('craft-button[command="--add-row"]')->text())->toContain('Add a coupon');
});

it('renders text expanders for text and textarea Controls', function () {
    $triggers = [
        ['trigger' => '@', 'boundary' => 'whitespace', 'label' => 'People', 'source' => 'users/text-expander-options'],
    ];
    $ui = Ui::make([
        Field::make('Name', Text::make('name')->textExpanderTriggers($triggers)),
        Field::make('Notes', Textarea::make('notes')->textExpanderTriggers($triggers)),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $input = $crawler->filter('input[name="settings[name]"]');
    $textarea = $crawler->filter('textarea[name="settings[notes]"]');
    $expanders = $crawler->filter('craft-text-expander');

    expect($payload->nodes[0]->control->props['textExpanderTriggers'])->toBe($triggers)
        ->and($payload->nodes[1]->control->props['textExpanderTriggers'])->toBe($triggers)
        ->and($expanders)->toHaveCount(2)
        ->and($expanders->each(fn (Crawler $node) => $node->attr('for')))->toBe([
            $input->attr('id'),
            $textarea->attr('id'),
        ])
        ->and($expanders->each(fn (Crawler $node) => $node->attr('slot')))->toBe(['input', 'input']);
});
