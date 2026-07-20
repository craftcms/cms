<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Operations\ElementRefs;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Sites;
use Illuminate\Support\Facades\Log;

class TestParseRefsElement extends Element
{
    public ?string $customRef = null;

    public ?string $customUrl = null;

    public ?string $body = null;

    public mixed $problem = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Parse Refs Test Element';
    }

    #[Override]
    public static function refHandle(): ?string
    {
        return 'test';
    }

    #[Override]
    public function getRef(): ?string
    {
        return $this->customRef;
    }

    #[Override]
    public function getUrl(): ?string
    {
        return $this->customUrl;
    }
}

class TestParseRefsQuery extends ElementQuery
{
    public mixed $recordedSiteId = '__not_called__';

    public mixed $recordedStatus = '__not_called__';

    public mixed $recordedId = '__not_called__';

    public function __construct(
        public array $elements = [],
    ) {}

    #[Override]
    public function siteId(mixed $value): static
    {
        $this->recordedSiteId = $value;

        return $this;
    }

    #[Override]
    public function status(array|string|null $value): static
    {
        $this->recordedStatus = $value;

        return $this;
    }

    #[Override]
    public function id(mixed $value): static
    {
        $this->recordedId = $value;

        return $this;
    }

    #[Override]
    public function all($columns = ['*']): array
    {
        return $this->elements;
    }
}

class TestParseRefsRefQuery extends TestParseRefsQuery
{
    public mixed $recordedRef = '__not_called__';

    public function ref(mixed $value): static
    {
        $this->recordedRef = $value;

        return $this;
    }
}

beforeEach(function () {
    $this->elements = $this->getMockBuilder(Elements::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['createElementQuery', 'getElementTypeByRefHandle'])
        ->getMock();

    $this->sites = $this->getMockBuilder(Sites::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getSiteByHandle', 'getSiteByUid'])
        ->getMock();

    $this->action = new ElementRefs($this->elements, $this->sites);
});

function createParseRefsElement(
    ?int $id = null,
    ?string $ref = null,
    ?string $url = null,
    ?string $title = null,
    ?string $body = null,
    mixed $problem = null,
): TestParseRefsElement {
    $element = new TestParseRefsElement;
    $element->id = $id;
    $element->customRef = $ref;
    $element->customUrl = $url;
    $element->title = $title;
    $element->body = $body;
    $element->problem = $problem;

    return $element;
}

function expectParseRefsElementType(int $times = 1): void
{
    test()->elements->expects(test()->exactly($times))
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);
}

function expectParseRefsQueries(TestParseRefsQuery ...$queries): void
{
    test()->elements->expects(test()->exactly(count($queries)))
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturnOnConsecutiveCalls(...$queries);
}

it('returns the original string when it does not contain reference syntax', function () {
    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    $this->elements->expects(test()->never())
        ->method('getElementTypeByRefHandle');

    expect($this->action->parseRefs('Plain text only'))->toBe('Plain text only');
});

it('returns the original string when it contains braces but no ref tags', function () {
    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    $this->elements->expects(test()->never())
        ->method('getElementTypeByRefHandle');

    expect($this->action->parseRefs('Before {not-a-ref} after'))->toBe('Before {not-a-ref} after');
});

it('uses an explicit fallback when the element type is unknown', function () {
    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('unknown')
        ->willReturn(null);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->parseRefs('Before {unknown:item || fallback text} after'))
        ->toBe('Before fallback text after');
});

it('uses the original tag as the fallback when a site handle cannot be resolved', function () {
    expectParseRefsElementType();

    $this->sites->expects(test()->once())
        ->method('getSiteByHandle')
        ->with('missing')
        ->willReturn(null);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->parseRefs('Before {test:entry@missing} after'))
        ->toBe('Before {test:entry@missing} after');
});

it('uses the default site id and resolves numeric id refs to urls', function () {
    $query = new TestParseRefsQuery([
        createParseRefsElement(id: 1, url: 'https://example.test/id-1'),
    ]);

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('Link: {test:1}', 7))
        ->toBe('Link: https://example.test/id-1')
        ->and($query->recordedSiteId)->toBe(7)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedId)->toBe([1]);
});

it('resolves site handles before querying elements', function () {
    $query = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'entry', url: 'https://example.test/handle-site'),
    ]);

    $this->sites->expects(test()->once())
        ->method('getSiteByHandle')
        ->with('secondary')
        ->willReturn(tap(new Site, function (Site $site) {
            $site->id = 2;
            $site->handle = 'secondary';
            $site->uid = 'site-2';
            $site->language = 'en-US';
        }));

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('{test:entry@secondary}'))
        ->toBe('https://example.test/handle-site')
        ->and($query->recordedSiteId)->toBe(2)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedId)->toBe('__not_called__')
        ->and($query->recordedRef)->toBe(['entry']);
});

it('resolves numeric site ids without looking up a site record', function () {
    $query = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'entry', url: 'https://example.test/numeric-site'),
    ]);

    $this->sites->expects(test()->never())
        ->method('getSiteByHandle');

    $this->sites->expects(test()->never())
        ->method('getSiteByUid');

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('{test:entry@4}'))
        ->toBe('https://example.test/numeric-site')
        ->and($query->recordedSiteId)->toBe(4)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedRef)->toBe(['entry']);
});

it('falls back when resolving a site uid throws an exception', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    expectParseRefsElementType();

    $this->sites->expects(test()->once())
        ->method('getSiteByUid')
        ->with($uuid)
        ->willThrowException(new SiteNotFoundException);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->parseRefs("{test:entry@$uuid||fallback}"))
        ->toBe('fallback');
});

it('resolves suffix refs even when the query does not support ref lookups', function () {
    $query = new TestParseRefsQuery([
        createParseRefsElement(ref: 'section/slug', url: 'https://example.test/slug-match'),
    ]);

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('{test:slug:summary}'))
        ->toBe('https://example.test/slug-match')
        ->and($query->recordedSiteId)->toBe('')
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedId)->toBe('__not_called__');
});

it('parses referenced attributes recursively', function () {
    $primaryQuery = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'primary-ref', body: 'See {test:nested-ref:title}'),
    ]);

    $nestedQuery = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'nested-ref', title: 'Nested title'),
    ]);

    expectParseRefsElementType(2);
    expectParseRefsQueries($primaryQuery, $nestedQuery);

    expect($this->action->parseRefs('Start {test:primary-ref:body} end'))
        ->toBe('Start See Nested title end')
        ->and($primaryQuery->recordedRef)->toBe(['primary-ref'])
        ->and($nestedQuery->recordedRef)->toBe(['nested-ref']);
});

it('uses the fallback when no matching element is found', function () {
    $query = new TestParseRefsRefQuery([]);

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('{test:missing||fallback}'))
        ->toBe('fallback')
        ->and($query->recordedRef)->toBe(['missing']);
});

it('logs and falls back when a referenced attribute cannot be converted to a string', function () {
    $query = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'error', problem: new stdClass),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'An exception was thrown when parsing the ref tag "{test:error:problem || fallback}"')
            && str_contains($message, 'could not be converted to string')
            && $context === ['CraftCms\\Cms\\Element\\Operations\\ElementRefs::getRefTokenReplacement']);

    expectParseRefsElementType();
    expectParseRefsQueries($query);

    expect($this->action->parseRefs('{test:error:problem || fallback}'))
        ->toBe('fallback')
        ->and($query->recordedRef)->toBe(['error']);
});

it('returns target ids for numeric id refs', function () {
    $query = new TestParseRefsQuery([
        createParseRefsElement(id: 3),
    ]);

    expectParseRefsElementType(2);
    expectParseRefsQueries($query);

    expect($this->action->targetIds('{test:3:url} {test:999}', 7))
        ->toBe([3])
        ->and($query->recordedSiteId)->toBe(7)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedId)->toBe([3, 999]);
});

it('returns target ids for named refs and suffix refs', function () {
    $suffixQuery = new TestParseRefsRefQuery([createParseRefsElement(id: 4, ref: 'section/slug')]);
    $directQuery = new TestParseRefsRefQuery([createParseRefsElement(id: 5, ref: 'direct-ref')]);
    $missingQuery = new TestParseRefsRefQuery([]);

    expectParseRefsElementType(3);
    expectParseRefsQueries($suffixQuery, $directQuery, $missingQuery);

    expect($this->action->targetIds('{test:slug} {test:direct-ref} {test:missing}'))
        ->toBe([4, 5])
        ->and($suffixQuery->recordedSiteId)->toBeNull()
        ->and($suffixQuery->recordedStatus)->toBeNull()
        ->and($suffixQuery->recordedRef)->toBe('slug')
        ->and($directQuery->recordedSiteId)->toBeNull()
        ->and($directQuery->recordedStatus)->toBeNull()
        ->and($directQuery->recordedRef)->toBe('direct-ref')
        ->and($missingQuery->recordedSiteId)->toBeNull()
        ->and($missingQuery->recordedStatus)->toBeNull()
        ->and($missingQuery->recordedRef)->toBe('missing');
});

it('returns no target ids when the site cannot be resolved', function () {
    expectParseRefsElementType();

    $this->sites->expects(test()->once())
        ->method('getSiteByHandle')
        ->with('missing')
        ->willReturn(null);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->targetIds('{test:entry@missing}'))->toBe([]);
});

it('replaces numeric and named refs that resolve to old targets', function () {
    $numericQuery = new TestParseRefsQuery([createParseRefsElement(id: 1)]);
    $matchingRefQuery = new TestParseRefsRefQuery([createParseRefsElement(id: 4, ref: 'section/slug')]);
    $keptRefQuery = new TestParseRefsRefQuery([createParseRefsElement(id: 5, ref: 'kept-ref')]);

    expectParseRefsElementType(3);
    expectParseRefsQueries($numericQuery, $matchingRefQuery, $keptRefQuery);

    expect($this->action->replaceTargetRefs(
        '{test:1@2:url} {test:section/slug:title || fallback} {test:kept-ref}',
        [1, 4],
        9,
    ))->toBe('{test:9@2:url} {test:9:title || fallback} {test:kept-ref}')
        ->and($numericQuery->recordedSiteId)->toBe(2)
        ->and($numericQuery->recordedId)->toBe(1)
        ->and($matchingRefQuery->recordedRef)->toBe('section/slug')
        ->and($keptRefQuery->recordedRef)->toBe('kept-ref');
});
