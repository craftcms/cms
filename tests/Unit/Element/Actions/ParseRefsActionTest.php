<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Actions\ParseRefsAction;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
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
        ->onlyMethods(['getElementTypeByRefHandle', 'createElementQuery'])
        ->getMock();

    $this->sites = $this->getMockBuilder(Sites::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getSiteByHandle', 'getSiteByUid'])
        ->getMock();

    $this->action = new ParseRefsAction($this->elements, $this->sites);
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

it('returns the original string when it does not contain reference syntax', function () {
    $this->elements->expects(test()->never())
        ->method('getElementTypeByRefHandle');

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->handle('Plain text only'))->toBe('Plain text only');
});

it('returns the original string when it contains braces but no ref tags', function () {
    $this->elements->expects(test()->never())
        ->method('getElementTypeByRefHandle');

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->handle('Before {not-a-ref} after'))->toBe('Before {not-a-ref} after');
});

it('uses an explicit fallback when the element type is unknown', function () {
    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('unknown')
        ->willReturn(null);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->handle('Before {unknown:item || fallback text} after'))
        ->toBe('Before fallback text after');
});

it('uses the original tag as the fallback when a site handle cannot be resolved', function () {
    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->sites->expects(test()->once())
        ->method('getSiteByHandle')
        ->with('missing')
        ->willReturn(null);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->handle('Before {test:entry@missing} after'))
        ->toBe('Before {test:entry@missing} after');
});

it('uses the default site id and resolves numeric id refs to urls', function () {
    $query = new TestParseRefsQuery([
        createParseRefsElement(id: 1, url: 'https://example.test/id-1'),
    ]);

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('Link: {test:1}', 7))
        ->toBe('Link: https://example.test/id-1')
        ->and($query->recordedSiteId)->toBe(7)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedId)->toBe([1]);
});

it('resolves site handles before querying elements', function () {
    $query = new TestParseRefsRefQuery([
        createParseRefsElement(ref: 'entry', url: 'https://example.test/handle-site'),
    ]);

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->sites->expects(test()->once())
        ->method('getSiteByHandle')
        ->with('secondary')
        ->willReturn(tap(new Site, function (Site $site) {
            $site->id = 2;
            $site->handle = 'secondary';
            $site->uid = 'site-2';
            $site->language = 'en-US';
        }));

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('{test:entry@secondary}'))
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

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->sites->expects(test()->never())
        ->method('getSiteByHandle');

    $this->sites->expects(test()->never())
        ->method('getSiteByUid');

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('{test:entry@4}'))
        ->toBe('https://example.test/numeric-site')
        ->and($query->recordedSiteId)->toBe(4)
        ->and($query->recordedStatus)->toBeNull()
        ->and($query->recordedRef)->toBe(['entry']);
});

it('falls back when resolving a site uid throws an exception', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->sites->expects(test()->once())
        ->method('getSiteByUid')
        ->with($uuid)
        ->willThrowException(new SiteNotFoundException);

    $this->elements->expects(test()->never())
        ->method('createElementQuery');

    expect($this->action->handle("{test:entry@$uuid||fallback}"))
        ->toBe('fallback');
});

it('resolves suffix refs even when the query does not support ref lookups', function () {
    $query = new TestParseRefsQuery([
        createParseRefsElement(ref: 'section/slug', url: 'https://example.test/slug-match'),
    ]);

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('{test:slug:summary}'))
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

    $this->elements->expects(test()->exactly(2))
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->elements->expects(test()->exactly(2))
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturnOnConsecutiveCalls($primaryQuery, $nestedQuery);

    expect($this->action->handle('Start {test:primary-ref:body} end'))
        ->toBe('Start See Nested title end')
        ->and($primaryQuery->recordedRef)->toBe(['primary-ref'])
        ->and($nestedQuery->recordedRef)->toBe(['nested-ref']);
});

it('uses the fallback when no matching element is found', function () {
    $query = new TestParseRefsRefQuery([]);

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('{test:missing||fallback}'))
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
            && $context === ['CraftCms\\Cms\\Element\\Actions\\ParseRefsAction::getRefTokenReplacement']);

    $this->elements->expects(test()->once())
        ->method('getElementTypeByRefHandle')
        ->with('test')
        ->willReturn(TestParseRefsElement::class);

    $this->elements->expects(test()->once())
        ->method('createElementQuery')
        ->with(TestParseRefsElement::class)
        ->willReturn($query);

    expect($this->action->handle('{test:error:problem || fallback}'))
        ->toBe('fallback')
        ->and($query->recordedRef)->toBe(['error']);
});
