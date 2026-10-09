<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Http\Request;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class McpActor
{
    public function __construct(private Request $request) {}

    public function user(): CraftUser
    {
        $user = $this->request->craftUser();

        if (! $user) {
            throw new ToolCallException('Authentication is required.');
        }

        return $user;
    }
}
