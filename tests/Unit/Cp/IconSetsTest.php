<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Events\IconSetsResolving;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Cp\IconSets\IconSet;
use CraftCms\Cms\Field\Data\IconData;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->iconDir = storage_path('framework/testing/custom-icons-'.Str::uuid());
    File::ensureDirectoryExists($this->iconDir);
    File::put("$this->iconDir/Arrow Thing.svg", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><title>Arrow</title><path id="arrow-path" d="M0 0h10v10z"/></svg>');
    File::put("$this->iconDir/not-an-icon.txt", 'nope');

    Cms::config()->customIconPath = $this->iconDir;
    app()->forgetInstance(IconSets::class);
});

afterEach(function () {
    Cms::config()->customIconPath = null;
    app()->forgetInstance(IconSets::class);
    File::deleteDirectory($this->iconDir);
});

it('parses custom icon set values', function (?array $expected, string $value) {
    expect(IconSets::parse($value))->toBe($expected);
})->with([
    [['custom', 'Arrow Thing'], 'custom:Arrow Thing'],
    [['my-set', 'arrow'], 'my-set:arrow'],
    [null, 'chevron-right'],
    [null, 'C:\\icons\\arrow.svg'],
    [null, 'C:/icons/arrow.svg'],
    [null, '@config/icons/arrow.svg'],
    [null, '<svg viewBox="0:0"></svg>'],
    [null, '1set:arrow'],
]);

it('registers a Custom icon set from customIconPath', function () {
    $iconSets = app(IconSets::class);
    $iconSet = $iconSets->getIconSetByHandle(IconSets::CUSTOM);

    expect($iconSet)->not->toBeNull()
        ->and($iconSet->getIcons())->toBe(['Arrow Thing'])
        ->and($iconSets->getIconSetNames())->toBe([
            IconSets::FONT_AWESOME => 'Font Awesome',
            IconSets::CUSTOM => 'Custom',
        ]);

    $svg = Icons::svg('custom:Arrow Thing');

    expect($svg)->toContain('d="M0 0h10v10z"')
        ->not->toContain('<title>')
        ->not->toContain('id="arrow-path"')
        ->and(Icons::svg('custom:missing'))->toBe('')
        ->and(Icons::svg('unknown-set:Arrow Thing'))->toBe('');
});

it('registers icon sets via the IconSetsResolving event', function () {
    Event::listen(function (IconSetsResolving $event) {
        $event->iconSets[] = new class('my-icons', 'My Icons') extends IconSet
        {
            public function getIcons(): array
            {
                return ['dot'];
            }

            public function getKeywords(string $icon): array
            {
                return ['circle'];
            }

            public function getSvg(string $icon): ?string
            {
                return $icon === 'dot' ? '<svg viewBox="0 0 1 1"><circle cx="0.5" cy="0.5" r="0.5"/></svg>' : null;
            }
        };
    });

    expect(app(IconSets::class)->getIconSetNames())->toBe([
        IconSets::FONT_AWESOME => 'Font Awesome',
        IconSets::CUSTOM => 'Custom',
        'my-icons' => 'My Icons',
    ])
        ->and(new IconData('dot', [], 'my-icons')->getKeywords())->toBe(['circle'])
        ->and(Icons::svg('my-icons:dot'))->toContain('<circle');
});

it('rejects icon sets with invalid handles', function (string $handle) {
    Event::listen(function (IconSetsResolving $event) use ($handle) {
        $event->iconSets[] = new class($handle, 'Bad') extends IconSet
        {
            public function getIcons(): array
            {
                return [];
            }

            public function getSvg(string $icon): ?string
            {
                return null;
            }
        };
    });

    app(IconSets::class)->getIconSets();
})->with([IconSets::FONT_AWESOME, '1set', 'bad:handle'])->throws(InvalidArgumentException::class);

it('normalizes and serializes Icon field values', function () {
    $field = new Icon;

    $value = $field->normalizeValue('custom:Arrow Thing', null);

    expect($value)->toBeInstanceOf(IconData::class)
        ->and($value->name)->toBe('Arrow Thing')
        ->and($value->set)->toBe('custom')
        ->and($value->styles)->toBe([])
        ->and($field->serializeValue($value, null))->toBe('custom:Arrow Thing');

    // Font Awesome icons aren’t prefixed with a set handle
    expect($field->serializeValue(new IconData('chevron-right', ['solid']), null))->toBe('chevron-right');
});

it('returns icon SVGs and keywords', function () {
    $value = new IconData('Arrow Thing', [], 'custom');

    expect($value->getSvg())->toContain('d="M0 0h10v10z"')
        ->and($value->getKeywords())->toBe([])
        ->and(new IconData('missing', [], 'custom')->getSvg())->toBeNull()
        ->and(new IconData('Arrow Thing', [], 'unknown-set')->getSvg())->toBeNull();
});

it('validates the Icon field’s icon sets', function () {
    $field = new Icon;

    expect($field->iconSets)->toBe([IconSets::FONT_AWESOME])
        ->and($field->validate(['iconSets']))->toBeTrue()
        ->and(new Icon(['iconSets' => [IconSets::CUSTOM]])->validate(['iconSets']))->toBeTrue();

    $field = Fields::createField(['type' => Icon::class, 'iconSets' => '']);

    expect($field->validate(['iconSets']))->toBeFalse()
        ->and($field->errors()->first('iconSets'))->toBe('At least one icon set must be selected.')
        ->and(new Icon(['iconSets' => ['nonexistent']])->validate(['iconSets']))->toBeFalse();
});
