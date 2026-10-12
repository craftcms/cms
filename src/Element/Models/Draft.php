<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @since 6.0.0
 */
class Draft extends BaseModel
{
    /** @use HasFactory<Factory<Draft>> */
    use HasFactory;

    #[\Override]
    protected $table = Table::DRAFTS;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'provisional' => 'bool',
        'trackChanges' => 'bool',
        'dateLastMerged' => 'datetime',
        'saved' => 'bool',
    ];

    public static function findElement(int $draftId, ?int $siteId = null): ?ElementInterface
    {
        $elementType = DB::table(Table::ELEMENTS)->where('draftId', $draftId)->value('type');
        if (! is_string($elementType) || ! is_a($elementType, ElementInterface::class, true)) {
            return null;
        }

        $query = $elementType::find()
            ->draftId($draftId)
            ->provisionalDrafts(null)
            ->status(null);

        return ($siteId === null ? $query->site('*') : $query->siteId($siteId))->one();
    }

    /**
     * Locks draft rows in ID order within the caller's transaction.
     */
    public static function lockIds(int ...$draftIds): void
    {
        DB::table(Table::DRAFTS)->whereIn('id', $draftIds)->orderBy('id')->lockForUpdate()->get(['id']);
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'id');
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function canonical(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'canonicalId');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creatorId');
    }
}
