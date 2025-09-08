<?php

namespace CraftCms\Cms\Shared\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\Models\Info;
use Throwable;

/**
 * @since 6.0.0
 */
trait FetchesInfo
{
    private ?Info $info = null;

    public function fetchInfo(bool $throw = false): Info
    {
        if (isset($this->info)) {
            return $this->info;
        }

        try {
            $info = Info::find(1);
        } catch (Throwable $e) {
            if ($throw) {
                throw $e;
            }

            return $this->info = new Info;
        }

        if (! $info) {
            $tableName = Table::INFO;
            abort(500, "The $tableName table is missing its row");
        }

        return $this->info = $info;
    }
}
