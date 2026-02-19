<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Support\Facades\Context;
use PDOException;

final class Info extends BaseModel
{
    use HasUid;

    protected $table = Table::INFO;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'schemaVersion' => '0',
        'configVersion' => '000000000000',
    ];

    #[\Override]
    protected static function booted(): void
    {
        self::saved(function ($model) {
            Context::addHidden('craft.info', $model);
        });
    }

    public static function fetch(bool $throwException = false): self
    {
        if (Context::hasHidden('craft.info')) {
            return Context::getHidden('craft.info');
        }

        try {
            $info = self::find(1);
        } catch (PDOException $e) {
            if ($throwException) {
                throw $e;
            }

            $info = new self;

            Context::addHidden('craft.info', $info);

            return $info;
        }

        if (! $info) {
            $tableName = Table::INFO;
            abort(500, "The $tableName table is missing its row");
        }

        Context::addHidden('craft.info', $info);

        return $info;
    }
}
