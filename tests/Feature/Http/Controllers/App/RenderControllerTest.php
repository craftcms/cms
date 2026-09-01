<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\App\RenderController;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('render elements validates required payload', function () {
    postJson(action([RenderController::class, 'elements']))
        ->assertJsonValidationErrors(['elements']);
});

test('render elements rejects invalid element ids', function () {
    postJson(action([RenderController::class, 'elements']), [
        'elements' => [
            [
                'type' => Entry::class,
                'id' => 'not-an-id',
                'siteId' => 1,
                'instances' => [[]],
            ],
        ],
    ])->assertBadRequest();
});

test('render elements returns chip html for entries', function () {
    $entry = EntryModel::factory()->create();

    $response = postJson(action([RenderController::class, 'elements']), [
        'elements' => [
            [
                'type' => Entry::class,
                'id' => $entry->id,
                'siteId' => Sites::getPrimarySite()->id,
                'instances' => [
                    'default' => [
                        'ui' => 'chip',
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath("elements.{$entry->id}.default", fn (string $html) => str_contains($html, 'chip') && str_contains($html, (string) $entry->id));
});

test('rendered nested element cards include the nested actions for their owner', function () {
    $address = createOwnedAddress();

    postJson(action([RenderController::class, 'elements']), [
        'elements' => [
            [
                'type' => Address::class,
                'id' => $address->id,
                'siteId' => $address->siteId,
                'ownerId' => auth()->id(),
                'instances' => [
                    ['context' => 'field', 'ui' => 'card', 'showActionMenu' => true, 'sortable' => true],
                ],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath(
            "elements.{$address->id}.0",
            fn (string $html) => str_contains($html, 'data-move-forward-action')
                && str_contains($html, 'data-move-backward-action')
                && str_contains($html, 'data-duplicate-action')
                && str_contains($html, 'data-delete-action'),
        );
});

test('client-supplied configs cannot request the nested actions without ownership', function () {
    $address = createOwnedAddress();

    postJson(action([RenderController::class, 'elements']), [
        'elements' => [
            [
                'type' => Address::class,
                'id' => $address->id,
                'siteId' => $address->siteId,
                // no ownerId — and an attempt to force the flag from the client
                'instances' => [
                    [
                        'context' => 'field',
                        'ui' => 'card',
                        'showActionMenu' => true,
                        'showNestedActions' => true,
                    ],
                ],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath(
            "elements.{$address->id}.0",
            fn (string $html) => ! str_contains($html, 'data-duplicate-action') && ! str_contains($html, 'data-delete-action'),
        );
});

function createOwnedAddress(): Address
{
    $address = new Address([
        'ownerId' => auth()->id(),
        'title' => 'Home',
        'countryCode' => 'US',
        'addressLine1' => '123 Fake Street',
        'locality' => 'San Francisco',
        'administrativeArea' => 'CA',
        'postalCode' => '94107',
    ]);
    app(Elements::class)->saveElement($address);

    return $address;
}

test('render components validates required payload', function () {
    postJson(action([RenderController::class, 'components']))
        ->assertJsonValidationErrors(['components']);
});

test('render components skips unresolved component types', function () {
    $response = postJson(action([RenderController::class, 'components']), [
        'components' => [
            [
                'type' => CraftCms\Cms\Section\Data\Section::class,
                'id' => 99999,
                'instances' => [[]],
            ],
        ],
        'withMenuItems' => true,
        'menuId' => 'sections-menu',
    ]);

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('components', [])
            ->where('menuItems', [])
            ->etc()
        );
});

test('render components returns chip and menu item html for sections', function () {
    $section = Section::factory()->create([
        'name' => 'Articles',
        'handle' => 'articles',
    ]);

    $response = postJson(action([RenderController::class, 'components']), [
        'components' => [
            [
                'type' => CraftCms\Cms\Section\Data\Section::class,
                'id' => $section->id,
                'instances' => [
                    [
                        'showHandle' => true,
                    ],
                ],
            ],
        ],
        'withMenuItems' => true,
        'menuId' => 'sections-menu',
    ]);

    $response->assertOk()
        ->assertJsonPath("components.CraftCms\\Cms\\Section\\Data\\Section.{$section->id}.0", fn (string $html) => str_contains($html, 'Articles') && str_contains($html, CraftCms\Cms\Section\Data\Section::class) && str_contains($html, "data-id=\"{$section->id}\""))
        ->assertJsonPath("menuItems.CraftCms\\Cms\\Section\\Data\\Section.{$section->id}", fn (string $html) => str_contains($html, 'Articles') && str_contains($html, CraftCms\Cms\Section\Data\Section::class) && str_contains($html, "data-id=\"{$section->id}\""));
});

test('render markdown validates required flavor', function () {
    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => '**bold**',
    ])->assertJsonValidationErrors(['flavor']);
});

test('render markdown rejects invalid flavors', function () {
    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => '**bold**',
        'flavor' => MarkdownService::FLAVOR_PRE_ENCODED,
    ])->assertJsonValidationErrors(['flavor']);
});

test('render markdown returns html using the requested flavor', function () {
    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => "line one\nline two",
        'flavor' => MarkdownService::FLAVOR_GFM_COMMENT,
    ])->assertExactJson([
        'html' => "<p>line one<br>\nline two</p>\n",
    ]);
});

test('render markdown can return inline-only html', function () {
    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => '**bold**',
        'flavor' => MarkdownService::FLAVOR_GFM,
        'inlineOnly' => true,
    ])->assertExactJson([
        'html' => '<strong>bold</strong>',
    ]);
});

test('render markdown encodes markdown before parsing', function () {
    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => '<b>**bold**</b>',
        'flavor' => MarkdownService::FLAVOR_GFM,
        'encode' => true,
    ])->assertExactJson([
        'html' => "<p>&lt;b&gt;<strong>bold</strong>&lt;/b&gt;</p>\n",
    ]);
});

test('render markdown sanitizes preview html when requested', function () {
    HtmlSanitizers::extend('paragraphs-only', new HtmlSanitizer(
        (new HtmlSanitizerConfig)->allowElement('p')
    ));

    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => '<p onclick="bad()">Hi</p><h1>Heading</h1>',
        'flavor' => MarkdownService::FLAVOR_GFM,
        'sanitizeHtml' => true,
        'htmlSanitizer' => 'paragraphs-only',
    ])->assertExactJson([
        'html' => "<p>Hi</p>\n",
    ]);
});

test('render markdown matches the markdown service output', function () {
    $markdown = "## Heading\n\n| A | B |\n| - | - |\n| 1 | 2 |";

    postJson(action([RenderController::class, 'markdown']), [
        'markdown' => $markdown,
        'flavor' => MarkdownService::FLAVOR_GFM,
    ])->assertExactJson([
        'html' => Markdown::parse($markdown, MarkdownService::FLAVOR_GFM),
    ]);
});
