<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Models;

use Carbon\CarbonInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Enums\ImportRunStatus;
use CraftCms\Cms\Import\Enums\ImportRunTrigger;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * One execution of a whole import plan.
 *
 * Each step run is kept in `steps`, keyed by the step's UID, in the order the steps run.
 *
 * @property int $id
 * @property string $uid
 * @property string|null $planUid
 * @property string $planHandle
 * @property ImportRunStatus $status
 * @property int $progress
 * @property int|null $userId
 * @property ImportRunTrigger|null $trigger
 * @property array<string, StepRun> $steps
 * @property CarbonInterface|null $dateStarted
 * @property CarbonInterface|null $dateFinished
 * @property CarbonInterface $dateCreated
 * @property CarbonInterface $dateUpdated
 *
 * @phpstan-type StepRun array{
 *     label: string,
 *     status: string,
 *     progress: int,
 *     total: int|null,
 *     source: array{name: string|null, size: int|null},
 *     counts: array{created: int, updated: int, unchanged: int, skipped: int, failed: int},
 *     failures: list<Failure>,
 * }
 * @phpstan-type Failure array{
 *     item: int,
 *     message: string,
 *     elementId: int|null,
 *     problems: list<array{path: list<string>, message: string}>,
 *     data: mixed,
 * }
 *
 * @since 6.0.0
 */
class ImportRun extends BaseModel
{
    #[Override]
    protected $table = Table::IMPORT_RUNS;

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ImportRunStatus::class,
            'trigger' => ImportRunTrigger::class,
            'steps' => 'array',
            'dateStarted' => 'datetime',
            'dateFinished' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
