<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Queue\TestClasses;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FakeQuery extends Builder
{
    private Collection $result;

    public function __construct()
    {
        parent::__construct(DB::connection());
    }

    public function setResult(Collection $result)
    {
        $this->result = $result;

        return $this;
    }

    #[\Override]
    public function get($columns = ['*']): Collection
    {
        if (! is_null($this->offset) && ! is_null($this->limit)) {
            return $this->result->skip($this->offset)->take($this->limit);
        }

        return $this->result;
    }

    #[\Override]
    public function count($columns = '*')
    {
        return $this->result->count();
    }
}
