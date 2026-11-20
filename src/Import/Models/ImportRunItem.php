<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Enums\ImportItemOutcome;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * What one of a run's items came to, and the element it imported into, if any.
 *
 * @property int $id
 * @property int $runId
 * @property string $stepUid
 * @property int $item The item's position in the step's data, from 1.
 * @property ImportItemOutcome $outcome
 * @property int|null $elementId
 *
 * @since 6.0.0
 */
class ImportRunItem extends Model
{
    #[Override]
    public $timestamps = false;

    #[Override]
    protected $table = Table::IMPORT_RUN_ITEMS;

    #[Override]
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'outcome' => ImportItemOutcome::class,
        ];
    }
}
