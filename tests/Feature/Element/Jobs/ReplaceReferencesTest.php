<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\Jobs\ReplaceReferences;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

function replaceReferencesMarkdownFixture(string $value)
{
    return EntryModel::factory()
        ->withField('body', MarkdownField::class, value: $value)
        ->createElementWithFields();
}

function replaceReferencesFieldInstanceUid(ElementInterface $element): string
{
    return $element->getFieldLayout()->getFieldByHandle('body')->layoutElement->uid;
}

it('queries the configured source ids for a source site', function () {
    $excluded = EntryModel::factory()->createElement();
    $included = EntryModel::factory(2)->create()->pluck('id')->all();

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: 1,
        refs: [
            ['fieldInstanceUid' => fake()->uuid(), 'sourceId' => $included[0]],
            ['fieldInstanceUid' => fake()->uuid(), 'sourceId' => $included[1]],
            ['fieldInstanceUid' => fake()->uuid(), 'sourceId' => $included[1]],
        ],
        oldTargetIds: [$excluded->id],
        newTargetId: $included[0],
    );

    $ids = $job->replacementQuery()
        ->collect()
        ->pluck('id')
        ->all();

    expect($ids)->toBe($included)
        ->not->toContain($excluded->id);
});

it('replaces markdown references while preserving tag syntax', function () {
    $oldTargets = EntryModel::factory(2)->create()->pluck('id')->all();
    $slugTarget = EntryModel::factory()->createElement(['slug' => 'slug-target']);
    $slugRef = $slugTarget->getSection()->handle.'/'.$slugTarget->slug;
    $newTarget = EntryModel::factory()->createElement();
    $result = replaceReferencesMarkdownFixture("{entry:{$oldTargets[0]}@1:url} {entry:{$oldTargets[1]}:title || Fallback} {entry:$slugRef:uri} {entry:{$oldTargets[0]}.5:url} {entry:news/post:url}");
    $source = $result->element;

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: $source->siteId,
        refs: [
            [
                'fieldInstanceUid' => replaceReferencesFieldInstanceUid($source),
                'sourceId' => $source->id,
            ],
        ],
        oldTargetIds: [...$oldTargets, $slugTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect($source->getFieldValue('body')->getRaw())
        ->toBe("{entry:$newTarget->id@1:url} {entry:$newTarget->id:title || Fallback} {entry:$newTarget->id:uri} {entry:{$oldTargets[0]}.5:url} {entry:news/post:url}");
});

it('refreshes tracked reference rows after saving changed content', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = replaceReferencesMarkdownFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: $source->siteId,
        refs: [
            [
                'fieldInstanceUid' => replaceReferencesFieldInstanceUid($source),
                'sourceId' => $source->id,
            ],
        ],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->handle();

    expect(DB::table(Table::FIELDREFERENCES)->pluck('targetId')->all())->toBe([$newTarget->id]);
});

it('does nothing when the source element has no matching refs', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = replaceReferencesMarkdownFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: $source->siteId,
        refs: [
            [
                'fieldInstanceUid' => replaceReferencesFieldInstanceUid($source),
                'sourceId' => $source->id + 1,
            ],
        ],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect($source->getFieldValue('body')->getRaw())->toBe("{entry:$oldTarget->id:url}");
});

it('does nothing when the field instance no longer exists', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = replaceReferencesMarkdownFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: $source->siteId,
        refs: [
            [
                'fieldInstanceUid' => fake()->uuid(),
                'sourceId' => $source->id,
            ],
        ],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect($source->getFieldValue('body')->getRaw())->toBe("{entry:$oldTarget->id:url}");
});

it('continues when saving a changed element fails', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = replaceReferencesMarkdownFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    $realElements = app(ElementsService::class);
    $elements = Mockery::mock($realElements)->makePartial();
    $elements->shouldReceive('saveElement')
        ->once()
        ->andThrow(new RuntimeException('Save failed'));
    Elements::swap($elements);

    $job = new TestReplaceReferences(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceSiteId: $source->siteId,
        refs: [
            [
                'fieldInstanceUid' => replaceReferencesFieldInstanceUid($source),
                'sourceId' => $source->id,
            ],
        ],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    try {
        $job->process($source);
    } finally {
        Elements::swap($realElements);
    }

    expect($source->getFieldValue('body')->getRaw())->toBe("{entry:$newTarget->id:url}");
});

class TestReplaceReferences extends ReplaceReferences
{
    public function replacementQuery(): Builder
    {
        return $this->getQuery();
    }

    public function process(ElementInterface $element): void
    {
        $this->processElement($element);
    }
}
