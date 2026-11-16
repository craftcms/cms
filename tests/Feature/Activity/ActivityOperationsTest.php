<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\ActivityEventRecorder;
use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Context;

function recordUpdate(string $id): ActivityEvent
{
    return Activities::record(new ElementUpdated(new ActivitySubject('test', $id, 'Test')));
}

it('groups the events recorded during an operation, and keeps what it was called', function () {
    $before = recordUpdate('before');
    [$first, $second] = Context::scope(
        fn () => [recordUpdate('first'), recordUpdate('second')],
        hidden: [ActivityEventRecorder::ContextOperation => new ActivityOperation(
            type: TestBatchOperation::class,
            id: 'batch-1',
            label: 'Nightly sync',
            data: ['batchUid' => 'abc'],
        )],
    );
    $after = recordUpdate('after');

    expect(ActivityEvent::query()->operation('batch-1')->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id])
        ->and($first->snapshots['operation'])->toBe(['type' => TestBatchOperation::class, 'label' => 'Nightly sync', 'batchUid' => 'abc'])
        ->and($before->operationId)->toBeNull()
        ->and($after->operationId)->toBeNull()
        ->and($after->snapshots)->not->toHaveKey('operation');
});

it('lets the operation’s type decide whether a viewer gets a link to it', function () {
    $event = Context::scope(
        fn () => recordUpdate('first'),
        hidden: [ActivityEventRecorder::ContextOperation => new ActivityOperation(TestBatchOperation::class, 'batch-1', 'Nightly sync')],
    );

    $operation = fn (bool $admin) => app(ActivityTimelinePresenter::class)
        ->events(collect([$event]), UserModel::factory()->admin($admin)->createElement())
        ->sole()['operation'];

    expect($operation(true))->toBe(['label' => 'Nightly sync', 'url' => '/batches/batch-1'])
        ->and($operation(false))->toBe(['label' => 'Nightly sync', 'url' => null]);
});

it('only takes operation types that can link to themselves', function () {
    new ActivityOperation(stdClass::class, 'batch-1', 'Nightly sync');
})->throws(InvalidArgumentException::class);

class TestBatchOperation implements ActivityOperationTypeInterface
{
    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return $viewer->admin ? "/batches/$id" : null;
    }
}
