<?php

namespace CraftCms\Cms\Shared\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

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
        'maintenance' => false,
        'configVersion' => '000000000000',
    ];

    protected function casts(): array
    {
        return [
            'maintenance' => 'bool',
        ];
    }

    protected static function booted(): void
    {
        self::saved(function ($model) {
            Context::add('craft.info', $model);
        });
    }

    public static function setIsInstalled(bool $isInstalled): void
    {
        Context::add('craft.isInstalled', $isInstalled);
    }

    public static function isInstalled(bool $strict = false): bool
    {
        if ($strict) {
            Context::forget('craft.isInstalled');
            Context::forget('craft.info');
        } elseif (Context::has('craft.isInstalled')) {
            return Context::get('craft.isInstalled');
        }

        try {
            DB::connection()->getPdo();
        } catch (PDOException $e) {
            if (! app()->runningInConsole()) {
                Log::error('There was a problem connecting to the database: '.$e->getMessage(), [__METHOD__]);
                report($e);
            }

            Context::add('craft.isInstalled', false);

            return false;
        }

        try {
            if ($strict) {
                $isInstalled = self::query()
                    ->where('id', 1)
                    ->exists();

                Context::add('craft.isInstalled', $isInstalled);

                return $isInstalled;
            }

            $isInstalled = ! empty(self::fetch(true)->id);

            Context::add('craft.isInstalled', $isInstalled);

            return $isInstalled;
        } catch (Throwable $e) {
            Log::error('There was a problem fetching the info row: '.$e->getMessage(), [__METHOD__]);
            report($e);

            Context::add('craft.isInstalled', false);

            return false;
        }
    }

    public static function fetch(bool $throwException = false): self
    {
        if (Context::has('craft.info')) {
            return Context::get('craft.info');
        }

        try {
            $info = self::find(1);
        } catch (PDOException $e) {
            if ($throwException) {
                throw $e;
            }

            $info = new self;

            Context::add('craft.info', $info);

            return $info;
        }

        if (! $info) {
            $tableName = Table::INFO;
            abort(500, "The $tableName table is missing its row");
        }

        Context::add('craft.info', $info);

        return $info;
    }
}
