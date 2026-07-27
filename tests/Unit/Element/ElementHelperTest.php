<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;

class TestElementHelperElement extends Element
{
    public ?string $uriFormat = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public function getUriFormat(): ?string
    {
        return $this->uriFormat;
    }
}

test('generates temporary slug', function () {
    $slug = ElementHelper::tempSlug();

    expect($slug)->toStartWith('__temp_')
        ->and(ElementHelper::isTempSlug($slug))->toBeTrue();
});

test('detects temporary slugs', function (bool $expected, ?string $slug) {
    expect(ElementHelper::isTempSlug($slug))->toBe($expected);
})->with([
    [false, null],
    [false, ''],
    [false, 'foo'],
    [false, '_temp_foo'],
    [false, 'foo__temp_bar'],
    [true, '__temp_'],
    [true, '__temp_foo'],
]);

test('generate slug', function (string $expected, string $input, ?bool $ascii = null, ?string $language = null) {
    $expected = str_replace('[separator-here]', Cms::config()->slugWordSeparator, $expected);

    expect(ElementHelper::generateSlug($input, $ascii, $language))->toBe($expected);
})->with([
    ['wordword', 'wordWord'],
    ['word[separator-here]word', 'word word'],
    ['foo[separator-here]0', 'foo 0'],
    ['word', 'word'],
    ['123456789', '123456789'],
    ['abc[separator-here]dfg', 'abc...dfg'],
    ['abc[separator-here]dfg', 'abc...(dfg)'],
    ['a[separator-here]b[separator-here]c', 'A-B-C'],
    ['test[separator-here]slug', 'test_slug'],
    ['audi[separator-here]s8[separator-here]4e[separator-here]2006[separator-here]2010', 'Audi S8 4E (2006-2010)'],
    ['こんにちは', 'こんにちは', false, null],
    ['сертификация', 'Сертификация', false, null],
]);

test('normalize slug', function (string $expected, string $slug) {
    $expected = str_replace('[separator-here]', Cms::config()->slugWordSeparator, $expected);

    expect(ElementHelper::normalizeSlug($slug))->toBe($expected);
})->with([
    ['wordword', 'wordWord'],
    ['word[separator-here]word', 'word word'],
    ['foo[separator-here]0', 'foo 0'],
    ['word', 'word'],
    ['123456789', '123456789'],
    ['abc...dfg', 'abc...dfg'],
    ['abc...dfg', 'abc...(dfg)'],
    [Element::HOMEPAGE_URI, Element::HOMEPAGE_URI],
    ['a-b-c', 'A-B-C'],
    ['test_slug', 'test_slug'],
    ['audi[separator-here]s8[separator-here]4e[separator-here]2006-2010', 'Audi S8 4E (2006-2010)'],
    ['こんにちは', 'こんにちは'],
    ['сертификация', 'Сертификация'],
]);

test('normalizes slug using lowercase when uppercase is disallowed', function () {
    Cms::config()->allowUppercaseInSlug = false;

    expect(ElementHelper::normalizeSlug('word WORD'))
        ->toBe('word'.Cms::config()->slugWordSeparator.'word');
});

test('detects slug tags in uri formats', function (bool $expected, string $uriFormat) {
    expect(ElementHelper::doesUriFormatHaveSlugTag($uriFormat))->toBe($expected);
})->with([
    [false, ''],
    [true, '{slug}'],
    [true, 'entry/slug'],
    [true, 'entry/{slug}'],
    [false, 'entry/{notASlug}'],
    [false, 'entry/{SLUG}'],
    [false, 'entry/data'],
]);

test('sets next and previous elements', function () {
    $elements = [
        $one = new TestElementHelperElement(['id' => 1]),
        $two = new TestElementHelperElement(['id' => 2]),
        $three = new TestElementHelperElement(['id' => 3]),
    ];

    ElementHelper::setNextPrevOnElements($elements);

    expect($one->getPrev())->toBeNull()
        ->and($one->getNext())->toBe($two)
        ->and($two->getPrev())->toBe($one)
        ->and($two->getNext())->toBe($three)
        ->and($three->getPrev())->toBe($two)
        ->and($three->getNext())->toBeNull();
});
