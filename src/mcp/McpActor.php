<?php

namespace craft\mcp;

use Craft;
use craft\elements\User;
use Mcp\Exception\ToolCallException;

class McpActor
{
    public function user(): User
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user instanceof User) {
            throw new ToolCallException('Authentication is required.');
        }

        return $user;
    }
}
