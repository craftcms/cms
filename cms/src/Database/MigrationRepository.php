<?php

namespace CraftCms\Cms\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;

/**
 * @internal
 *
 * @since 6.0.0
 */
final class MigrationRepository extends DatabaseMigrationRepository
{
    protected ?string $track = null;

    public function track(?string $track): self
    {
        $this->track = $track;

        return $this;
    }

    public function getTrack(): ?string
    {
        return $this->track;
    }

    /**
     * {@inheritdoc}
     */
    public function log($file, $batch): void
    {
        $record = ['migration' => $file, 'batch' => $batch, 'track' => $this->track];

        $this->table()->insert($record);
    }

    protected function table(): Builder
    {
        return parent::table()->where('track', $this->track);
    }

    /**
     * Create the migration repository data store.
     */
    public function createRepository(): void
    {
        parent::createRepository();

        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->table($this->table, function (Blueprint $table) {
            $table->string('track')->nullable()->after('id');
        });
    }
}
