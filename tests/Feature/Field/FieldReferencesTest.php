<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\DeletionBlockers\FieldReferencesDeletionBlocker;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Jobs\ReplaceReferences;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementsController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

function fieldReferenceRows(): array
{
    return DB::table(Table::FIELDREFERENCES)
        ->select(['fieldId', 'fieldInstanceUid', 'sourceId', 'sourceSiteId', 'targetId'])
        ->orderBy('sourceId')
        ->orderBy('sourceSiteId')
        ->orderBy('fieldId')
        ->orderBy('fieldInstanceUid')
        ->orderBy('targetId')
        ->get()
        ->map(fn (object $row) => (array) $row)
        ->all();
}

function markdownReferenceFixture(string $value)
{
    return EntryModel::factory()
        ->withField('body', MarkdownField::class, value: $value)
        ->createElementWithFields();
}

it('tracks valid numeric and named markdown reference tags', function () {
    $target = EntryModel::factory()->createElement();
    $slugTarget = EntryModel::factory()->createElement(['slug' => 'slug-target']);
    $slugRef = $slugTarget->getSection()->handle.'/'.$slugTarget->slug;

    $result = markdownReferenceFixture("Valid {entry:$target->id:url}\nSlug {entry:$slugRef:url}\nDecimal {entry:{$target->id}.5:url}\nWrong {asset:$target->id:url}\nMissing {entry:999999:url}");
    $field = $result->field('body');
    $source = $result->element;

    expect(fieldReferenceRows())->toMatchArray([
        [
            'fieldId' => $field->id,
            'fieldInstanceUid' => $source->getFieldLayout()->getFieldByHandle('body')->layoutElement->uid,
            'sourceId' => $source->id,
            'sourceSiteId' => $source->siteId,
            'targetId' => $target->id,
        ],
        [
            'fieldId' => $field->id,
            'fieldInstanceUid' => $source->getFieldLayout()->getFieldByHandle('body')->layoutElement->uid,
            'sourceId' => $source->id,
            'sourceSiteId' => $source->siteId,
            'targetId' => $slugTarget->id,
        ],
    ]);
});

it('updates and removes markdown reference rows when content changes', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = markdownReferenceFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    $source->setFieldValue('body', "{entry:$newTarget->id:url}");
    Elements::saveElement($source);

    expect(DB::table(Table::FIELDREFERENCES)->pluck('targetId')->all())->toBe([$newTarget->id]);

    $source->setFieldValue('body', 'No references');
    Elements::saveElement($source);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(0);
});

it('tracks drafts but not revisions', function () {
    $target = EntryModel::factory()->createElement();
    $result = markdownReferenceFixture('No references');
    $source = $result->element;

    $draft = app(Drafts::class)->createDraft($source, User::findOne()->id);
    $draft->setFieldValue('body', "{entry:$target->id:url}");
    Elements::saveElement($draft);

    $source->setFieldValue('body', "{entry:$target->id:url}");
    Elements::saveElement($source);
    $revisionId = app(Revisions::class)->createRevision($source, force: true);

    expect(DB::table(Table::FIELDREFERENCES)->where('sourceId', $draft->id)->exists())->toBeTrue()
        ->and(DB::table(Table::FIELDREFERENCES)->where('sourceId', $revisionId)->exists())->toBeFalse();
});

it('ignores soft-deleted source elements in blockers', function () {
    $target = EntryModel::factory()->createElement();
    $result = markdownReferenceFixture("{entry:$target->id:url}");

    expect(new FieldReferencesDeletionBlocker(ElementCollection::make([$target]), false)->isActive())->toBeTrue();

    Elements::deleteElement($result->element);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(1)
        ->and(new FieldReferencesDeletionBlocker(ElementCollection::make([$target]), false)->isActive())->toBeFalse();
});

it('cleans references when tracking fields are deleted', function () {
    $target = EntryModel::factory()->createElement();
    $field = Fields::createField([
        'type' => MarkdownField::class,
        'name' => 'Body',
        'handle' => 'body',
    ]);

    Fields::saveField($field);

    $source = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->createElement();
    $source->setFieldValue('body', "{entry:$target->id:url}");
    Elements::saveElement($source);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(1);

    Fields::deleteField($field);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(0);
});

it('cleans references when tracking fields are converted to non-tracking fields', function () {
    $target = EntryModel::factory()->createElement();
    $field = Fields::createField([
        'type' => MarkdownField::class,
        'name' => 'Body',
        'handle' => 'body',
    ]);

    Fields::saveField($field);

    $source = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->createElement();
    $source->setFieldValue('body', "{entry:$target->id:url}");
    Elements::saveElement($source);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(1);

    Fields::saveField(Fields::createField([
        'id' => $field->id,
        'uid' => $field->uid,
        'type' => PlainText::class,
        'name' => 'Body',
        'handle' => 'body',
    ]));

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(0);
});

it('cleans references when field layout instances are removed', function () {
    $target = EntryModel::factory()->createElement();
    $result = markdownReferenceFixture("{entry:$target->id:url}");
    $layout = $result->element->getFieldLayout();

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(1);

    $layout->setTabs([]);
    Fields::saveLayout($layout);

    expect(DB::table(Table::FIELDREFERENCES)->count())->toBe(0);
});

it('queues reference replacement from the delete elements controller', function () {
    Queue::fake();

    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = markdownReferenceFixture("{entry:$oldTarget->id:url}");
    $source = $result->element;

    postJson(action([DeleteElementsController::class, 'replaceReferences']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$oldTarget->id],
        'newTargetId' => $newTarget->id,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Reference queued to be replaced.');

    Queue::assertPushed(ReplaceReferences::class, fn (ReplaceReferences $job) => $job->sourceElementType === EntryElement::class &&
        $job->targetElementType === EntryElement::class &&
        $job->sourceSiteId === $source->siteId &&
        $job->refs === [[
            'fieldInstanceUid' => $source->getFieldLayout()->getFieldByHandle('body')->layoutElement->uid,
            'sourceId' => $source->id,
        ]] &&
        $job->oldTargetIds === [$oldTarget->id] &&
        $job->newTargetId === $newTarget->id);
});
