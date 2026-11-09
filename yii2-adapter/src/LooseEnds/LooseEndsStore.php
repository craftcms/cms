<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use Carbon\CarbonImmutable;
use CraftCms\Cms\Support\Query;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Throwable;

/**
 * Stores the runtime usage the Yii2 Loose Ends utility shows, as it happens.
 *
 * Findings from the report’s checks aren’t stored, since the checks run each time the report loads.
 *
 * @internal
 */
#[Singleton]
class LooseEndsStore
{
    public const string TABLE = 'yii2looseends';

    /**
     * Caches how many findings the checks returned the last time the report loaded, for the utility’s badge.
     */
    public const string FINDING_COUNT_CACHE_KEY = 'yii2-loose-ends:finding-count';

    /**
     * How long to wait before writing runtime usage that was already written, in seconds.
     */
    private const int USAGE_WRITE_INTERVAL = 3600;

    private ?bool $tableExists = null;

    /**
     * Whether the table was dropped during this request, so usage recorded afterward doesn’t create it again.
     */
    private bool $dropped = false;

    public function tableExists(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        try {
            return $this->tableExists = Schema::hasTable(self::TABLE);
        } catch (Throwable) {
            return false;
        }
    }

    public function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        try {
            $this->createTable();
        } catch (Throwable $e) {
            // Another request may have created it first.
            $this->tableExists = null;

            if (!$this->tableExists()) {
                throw $e;
            }
        }

        $this->tableExists = true;
    }

    private function createTable(): void
    {
        Schema::create(self::TABLE, function(Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->char('fingerprint', 40);
            $table->string('section', 20);
            $table->string('severity', 20);
            $table->text('message');
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->dateTime('lastSeen');
            $table->unique(['key', 'fingerprint']);
        });
    }

    /**
     * Drops the table.
     */
    public function drop(): void
    {
        Schema::dropIfExists(self::TABLE);
        Cache::forget(self::FINDING_COUNT_CACHE_KEY);
        $this->tableExists = false;
        $this->dropped = true;
    }

    /**
     * Stores runtime usage.
     *
     * @param list<array{key: string, message: string, section: Section, severity: Severity, file: string|null, line: int|null}> $usages
     */
    public function recordUsages(array $usages): void
    {
        if ($this->dropped) {
            return;
        }

        $now = Query::prepareDateForDb(CarbonImmutable::now());

        foreach ($usages as $usage) {
            $fingerprint = $this->fingerprint($usage['file'], $usage['line']);
            $cacheKey = $this->cacheKey($usage['key'], $fingerprint);

            if (!Cache::add($cacheKey, true, self::USAGE_WRITE_INTERVAL)) {
                continue;
            }

            try {
                $this->ensureTable();

                DB::table(self::TABLE)->upsert([
                    'key' => $usage['key'],
                    'fingerprint' => $fingerprint,
                    'section' => $usage['section']->value,
                    'severity' => $usage['severity']->value,
                    'message' => $usage['message'],
                    'file' => $usage['file'],
                    'line' => $usage['line'],
                    'lastSeen' => $now,
                ], ['key', 'fingerprint'], ['message', 'lastSeen']);
            } catch (Throwable $e) {
                // Let the next occurrence try again, rather than waiting out the interval.
                Cache::forget($cacheKey);

                throw $e;
            }
        }
    }

    /**
     * Returns the stored runtime usage, most recently seen first.
     *
     * @return Collection<int, stdClass>
     */
    public function usages(): Collection
    {
        if (!$this->tableExists()) {
            return collect();
        }

        return DB::table(self::TABLE)->orderByDesc('lastSeen')->get();
    }

    /**
     * Returns the number of items the report showed the last time it loaded, plus any runtime usage stored since.
     */
    public function outstandingCount(): int
    {
        $count = (int)Cache::get(self::FINDING_COUNT_CACHE_KEY, 0);

        if (!$this->tableExists()) {
            return $count;
        }

        try {
            return $count + DB::table(self::TABLE)->count();
        } catch (Throwable) {
            return $count;
        }
    }

    /**
     * Deletes runtime usage that the user has marked resolved. It comes back if it’s seen again.
     */
    public function resolveUsage(int $id): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (!$row) {
            return false;
        }

        DB::table(self::TABLE)->where('id', $id)->delete();
        Cache::forget($this->cacheKey($row->key, $row->fingerprint));

        return true;
    }

    private function fingerprint(?string $file, ?int $line): string
    {
        return sha1(($file ?? '') . ':' . ($line ?? ''));
    }

    private function cacheKey(string $key, string $fingerprint): string
    {
        return 'yii2-loose-ends:' . sha1("$key|$fingerprint");
    }
}
