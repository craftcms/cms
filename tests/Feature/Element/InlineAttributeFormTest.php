<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Events\ElementInlineAttributeInputHtmlResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

function inlineAttributePayload(string $html): array
{
    $crawler = new Crawler($html);

    expect($crawler->filter('input, textarea, select')->count())->toBe(0);

    return json_decode($crawler->filter('craft-inline-attribute-form')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);
}

it('renders entry dates and slug as namespaced Form controls', function (string $attribute, string $component) {
    $entry = EntryModel::factory()->createElement();
    $entry->postDate = new DateTimeImmutable('2026-05-03 09:17:00', new DateTimeZone(Cms::timezone()));
    $entry->expiryDate = null;
    $entry->slug = 'a&b="quoted"';

    $payload = inlineAttributePayload(InputNamespace::namespaceInputs(
        fn () => $entry->getInlineAttributeInputHtml($attribute),
        'index[element-42]',
    ));

    expect($payload['nodes'][0]['control']['component'])->toBe($component)
        ->and($payload['nodes'][0]['control']['path'])->toBe(['index', 'element-42', $attribute])
        ->and($payload['values']['index']['element-42'][$attribute])->toBe(match ($attribute) {
            'slug' => 'a&b="quoted"',
            'postDate' => ['date' => '2026-05-03', 'time' => '09:17', 'timezone' => Cms::timezone()],
            'expiryDate' => ['date' => '', 'time' => '', 'timezone' => Cms::timezone()],
        });
})->with([
    ['postDate', 'craft:date-time'],
    ['expiryDate', 'craft:date-time'],
    ['slug', 'craft:text'],
]);

it('retains the system timezone when displaying entry dates', function () {
    $entry = EntryModel::factory()->createElement();
    $entry->postDate = new DateTimeImmutable('2026-05-03 09:17:00', new DateTimeZone('+05:00'));
    $expected = $entry->postDate->setTimezone(new DateTimeZone(Cms::timezone()));

    $payload = inlineAttributePayload($entry->getInlineAttributeInputHtml('postDate'));

    expect($payload['values']['postDate'])->toBe([
        'date' => $expected->format('Y-m-d'),
        'time' => $expected->format('H:i'),
        'timezone' => Cms::timezone(),
    ])->and($entry->postDate->getTimezone()->getName())->toBe('+05:00');
});

it('uses the author selector criteria limit and IDs', function () {
    $entry = EntryModel::factory()->createElement();
    $payload = inlineAttributePayload($entry->getInlineAttributeInputHtml('authors'));

    expect($payload['nodes'][0]['control']['component'])->toBe('craft:element-select')
        ->and($payload['nodes'][0]['control']['path'])->toBe(['authorIds'])
        ->and($payload['nodes'][0]['control']['props']['criteria']['can'])->toBe('viewEntries:'.$entry->getSection()->uid)
        ->and($payload['nodes'][0]['control']['props']['limit'])->toBe($entry->getSection()->maxAuthors)
        ->and($payload['values']['authorIds'])->toBe($entry->getAuthorIds());
});

it('disables author controls when the author cannot be changed', function () {
    $entry = EntryModel::factory()->createElement();
    Gate::before(fn ($user, $ability) => $ability === 'changeAuthor' ? false : null);

    $payload = inlineAttributePayload($entry->getInlineAttributeInputHtml('authors'));

    expect($payload['nodes'][0]['control']['mode'])->toBe('disabled');
});

it('renders asset alt text and keeps folders without inline inputs', function () {
    $asset = Asset::factory()->createElement();
    $asset->alt = "First line\nSecond & <line>";
    $payload = inlineAttributePayload($asset->getInlineAttributeInputHtml('alt'));

    expect($payload['nodes'][0]['control']['component'])->toBe('craft:textarea')
        ->and($payload['values']['alt'])->toBe("First line\nSecond & <line>");

    $asset->isFolder = true;

    expect($asset->getInlineAttributeInputHtml('alt'))->toBe('');
});

it('reuses field configuration and the field instance handle', function () {
    $field = Field::factory()->create([
        'type' => PlainText::class,
        'handle' => 'body',
        'settings' => ['multiline' => true, 'initialRows' => 3, 'charLimit' => 80],
    ]);
    $entry = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $field->uid, 'handle' => 'summary']),
        ]))
        ->createElement();
    $layoutElement = $entry->getFieldLayout()->getCustomFields()[0]->layoutElement;
    $entry->setFieldValue('summary', 'Existing text');
    $nativeControl = $layoutElement->getField()->formControl(new FieldContext('summary', 'Existing text', $entry));

    $payload = inlineAttributePayload(InputNamespace::namespaceInputs(
        fn () => $entry->getInlineAttributeInputHtml("fieldInstance:$layoutElement->uid"),
        'index[element-42][fields]',
    ));

    expect($payload['nodes'][0]['control']['component'])->toBe($nativeControl->component())
        ->and($payload['nodes'][0]['control']['props'])->toBe($nativeControl->props())
        ->and($payload['nodes'][0]['control']['path'])->toBe(['index', 'element-42', 'fields', 'summary'])
        ->and($payload['values']['index']['element-42']['fields']['summary'])->toBe('Existing text');
});

it('keeps HTML event overrides authoritative for native controls', function () {
    $entry = EntryModel::factory()->createElement();
    $calls = 0;
    Event::listen(ElementInlineAttributeInputHtmlResolving::class, function ($event) use (&$calls) {
        $calls++;
        $event->html = '<input name="slug" value="Plugin">';
    });

    expect($entry->getInlineAttributeInputHtml('slug'))->toBe('<input name="slug" value="Plugin">')
        ->and($calls)->toBe(1);
});

it('does not create controls for fields hidden by their layout', function () {
    $field = Field::factory()->create(['type' => PlainText::class, 'handle' => 'body']);
    $entry = EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($field))->createElement();
    $entry->setFieldValue('body', 'Visible preview');
    Event::listen(FieldLayoutComponentShowInFormResolving::class, function ($event) {
        $event->showInForm = false;
    });

    expect($entry->getInlineAttributeInputHtml("field:$field->uid"))->toBe($entry->getAttributeHtml("field:$field->uid"));
});

it('allows inherited protected HTML overrides to call the native parent', function () {
    $entry = EntryModel::factory()->createElement();
    $plugin = new class(['slug' => $entry->slug]) extends Entry
    {
        protected function inlineAttributeInputHtml(string $attribute): string
        {
            return '<span data-plugin></span>'.parent::inlineAttributeInputHtml($attribute);
        }
    };

    $html = $plugin->getInlineAttributeInputHtml('slug');

    expect(new Crawler($html)->filter('[data-plugin]')->count())->toBe(1)
        ->and(inlineAttributePayload($html)['values']['slug'])->toBe($entry->slug);
});
