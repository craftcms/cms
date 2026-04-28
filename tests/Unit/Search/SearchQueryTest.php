<?php

declare(strict_types=1);

use CraftCms\Cms\Search\SearchQuery;
use CraftCms\Cms\Search\SearchQueryTerm;
use CraftCms\Cms\Search\SearchQueryTermGroup;

test('simple single word query', function () {
    $query = new SearchQuery('foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTerm::class);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->exclude)->toBeFalse();
    expect($tokens[0]->exact)->toBeFalse();
    expect($tokens[0]->subLeft)->toBeFalse();
    expect($tokens[0]->subRight)->toBeTrue();
});

test('multiple word query produces multiple terms', function () {
    $query = new SearchQuery('foo bar baz');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(3);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[1]->term)->toBe('bar');
    expect($tokens[2]->term)->toBe('baz');
});

test('exclude prefix', function () {
    $query = new SearchQuery('-foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->exclude)->toBeTrue();
});

test('attribute specific term with single colon', function () {
    $query = new SearchQuery('title:foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->attribute)->toBe('title');
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->exact)->toBeFalse();
});

test('attribute specific term with double colon is exact', function () {
    $query = new SearchQuery('title::foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->attribute)->toBe('title');
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->exact)->toBeTrue();
    expect($tokens[0]->subLeft)->toBeFalse();
    expect($tokens[0]->subRight)->toBeFalse();
});

test('wildcard left', function () {
    $query = new SearchQuery('*foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->subLeft)->toBeTrue();
    expect($tokens[0]->subRight)->toBeFalse();
});

test('wildcard right', function () {
    $query = new SearchQuery('foo*');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->subRight)->toBeTrue();
    expect($tokens[0]->subLeft)->toBeFalse();
});

test('wildcard both sides', function () {
    $query = new SearchQuery('*foo*');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo');
    expect($tokens[0]->subLeft)->toBeTrue();
    expect($tokens[0]->subRight)->toBeTrue();
});

test('quoted phrase with double quotes', function () {
    $query = new SearchQuery('"foo bar"');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo bar');
    expect($tokens[0]->phrase)->toBeTrue();
});

test('quoted phrase with single quotes', function () {
    $query = new SearchQuery("'foo bar'");

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->term)->toBe('foo bar');
    expect($tokens[0]->phrase)->toBeTrue();
});

test('OR operator creates term group', function () {
    $query = new SearchQuery('foo OR bar');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTermGroup::class);
    expect($tokens[0]->terms)->toHaveCount(2);
    expect($tokens[0]->terms[0]->term)->toBe('foo');
    expect($tokens[0]->terms[1]->term)->toBe('bar');
});

test('multiple OR operators create single term group', function () {
    $query = new SearchQuery('foo OR bar OR baz');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTermGroup::class);
    expect($tokens[0]->terms)->toHaveCount(3);
});

test('OR combined with regular terms', function () {
    $query = new SearchQuery('hello foo OR bar world');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(3);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTerm::class);
    expect($tokens[0]->term)->toBe('hello');
    expect($tokens[1])->toBeInstanceOf(SearchQueryTermGroup::class);
    expect($tokens[1]->terms)->toHaveCount(2);
    expect($tokens[2])->toBeInstanceOf(SearchQueryTerm::class);
    expect($tokens[2]->term)->toBe('world');
});

test('exclude with attribute', function () {
    $query = new SearchQuery('-title:foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->exclude)->toBeTrue();
    expect($tokens[0]->attribute)->toBe('title');
    expect($tokens[0]->term)->toBe('foo');
});

test('attribute wildcard matches any value', function () {
    $query = new SearchQuery('title:*');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0]->attribute)->toBe('title');
    expect($tokens[0]->subLeft)->toBeTrue();
    expect($tokens[0]->subRight)->toBeFalse();
    expect($tokens[0]->term)->toBe('');
});

test('default term options are applied', function () {
    $query = new SearchQuery('foo', [
        'subLeft' => true,
        'subRight' => false,
    ]);

    $tokens = $query->getTokens();

    expect($tokens[0]->subLeft)->toBeTrue();
    expect($tokens[0]->subRight)->toBeFalse();
});

test('explicit wildcards override default options', function () {
    $query = new SearchQuery('foo*', [
        'subLeft' => true,
        'subRight' => false,
    ]);

    $tokens = $query->getTokens();

    expect($tokens[0]->subLeft)->toBeFalse();
    expect($tokens[0]->subRight)->toBeTrue();
});

test('getQuery returns original query string', function () {
    $query = new SearchQuery('foo bar');

    expect($query->getQuery())->toBe('foo bar');
});

test('empty query produces no tokens', function () {
    $query = new SearchQuery('');

    expect($query->getTokens())->toBeEmpty();
});

test('OR at start of query is treated as regular search', function () {
    $query = new SearchQuery('OR foo');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTerm::class);
    expect($tokens[0]->term)->toBe('foo');
});

test('OR at end of query is ignored', function () {
    $query = new SearchQuery('foo OR');

    $tokens = $query->getTokens();

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(SearchQueryTerm::class);
    expect($tokens[0]->term)->toBe('foo');
});
