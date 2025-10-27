<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

abstract class GarbageCollectionAction
{
    use InteractsWithIO;

    public function __construct(
        protected readonly GarbageCollection $garbageCollection,
        protected readonly GeneralConfig $generalConfig,
    ) {
        $this->input = new ArrayInput([]);
        $this->output = $this->garbageCollection->silent
            ? new NullOutput
            : new OutputStyle($this->input, new ConsoleOutput);
        $this->components = new Factory($this->output);
    }

    abstract public function __invoke(): void;

    protected function shouldHardDelete(): bool
    {
        return $this->generalConfig->softDeleteDuration || $this->garbageCollection->deleteAllTrashed;
    }

    protected function hardDeleteQuery(Builder $query, ?string $tableAlias = null): Builder
    {
        $tableAlias = $tableAlias ? "$tableAlias." : '';

        return $query
            ->whereNotNull("{$tableAlias}dateDeleted")
            ->when(
                ! $this->garbageCollection->deleteAllTrashed,
                fn (Builder $query) => $query->where(
                    "{$tableAlias}dateDeleted",
                    '<',
                    now()->subSeconds($this->generalConfig->softDeleteDuration),
                ),
            );
    }
}
