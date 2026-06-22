<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Jobs\ReplaceRelations;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

function replaceRelationsTargetIds(int $sourceId, int $fieldId): array
{
    return DB::table(Table::RELATIONS)
        ->where('sourceId', $sourceId)
        ->where('fieldId', $fieldId)
        ->orderBy('sortOrder')
        ->pluck('targetId')
        ->all();
}

it('queries the configured source element ids', function () {
    $excluded = EntryModel::factory()->createElement();
    $included = EntryModel::factory(2)->create()->pluck('id')->all();

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: $included,
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

it('replaces stored relation query values', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $keptTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, value: [$oldTarget->id, $keptTarget->id])
        ->createElementWithFields();
    $source = $result->element;
    $field = $result->fields->get('relatedEntries');

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: [$source->id],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->handle();

    expect(replaceRelationsTargetIds($source->id, $field->id))->toBe([
        $newTarget->id,
        $keptTarget->id,
    ]);
});

it('replaces raw array values and removes duplicate target ids', function () {
    $oldTargets = EntryModel::factory(2)->create()->pluck('id')->all();
    $keptTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class)
        ->createElementWithFields();
    $source = $result->element;
    $field = $result->fields->get('relatedEntries');
    $source->setCustomFieldRawValue('relatedEntries', [
        $oldTargets[0],
        $keptTarget->id,
        $oldTargets[1],
        $newTarget->id,
        null,
        '',
    ]);

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: [$source->id],
        oldTargetIds: $oldTargets,
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect(replaceRelationsTargetIds($source->id, $field->id))->toBe([
        $newTarget->id,
        $keptTarget->id,
    ]);
});

it('does nothing when the element has no matching relation fields', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    $result = EntryModel::factory()
        ->withField('relatedUsers', Users::class)
        ->createElementWithFields();
    $source = $result->element;

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: [$source->id],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect(DB::table(Table::RELATIONS)->where('sourceId', $source->id)->count())->toBe(0);
});

it('does not save when no target ids changed', function () {
    $keptTargets = EntryModel::factory(2)->create()->pluck('id')->all();
    $missingTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, value: $keptTargets)
        ->createElementWithFields();
    $source = $result->element;
    $field = $result->fields->get('relatedEntries');

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: [$source->id],
        oldTargetIds: [$missingTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect(replaceRelationsTargetIds($source->id, $field->id))->toBe($keptTargets);
});

it('continues when saving a changed element fails', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class)
        ->createElementWithFields();
    $source = $result->element;
    $source->setCustomFieldRawValue('relatedEntries', [$oldTarget->id]);

    Elements::shouldReceive('saveElement')
        ->once()
        ->andThrow(new RuntimeException('Save failed'));

    $job = new TestReplaceRelations(
        sourceElementType: EntryElement::class,
        targetElementType: EntryElement::class,
        sourceIds: [$source->id],
        oldTargetIds: [$oldTarget->id],
        newTargetId: $newTarget->id,
    );

    $job->process($source);

    expect($source->getCustomFieldRawValue('relatedEntries'))->toBe([$newTarget->id]);
});

class TestReplaceRelations extends ReplaceRelations
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
