<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;

/**
 * Reads from the write connection on unsafe MCP requests.
 *
 * MCP requests are sessionless, so `PreventStaleDatabaseReads` can't remember
 * that a previous tool call wrote data.
 *
 * @since 6.0.0
 */
readonly class UseWriteConnection
{
    public function __construct(
        private Connection $db,
    ) {}

    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->db->useWriteConnectionWhenReading(! $request->isMethodSafe());

        return $next($request);
    }
}
