<?php

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\mcp\ServerFactory;
use Mcp\Server\Transport\StdioTransport;
use yii\console\ExitCode;

class McpController extends Controller
{
    public ?string $user = null;

    public function options($actionID): array
    {
        return [...parent::options($actionID), ...($actionID === 'serve' ? ['user'] : [])];
    }

    /** Run the MCP server on stdin/stdout as a named Craft user. */
    public function actionServe(): int
    {
        if (!$this->user) {
            $this->stderr("Provide --user with a user ID, username, or email.\n");
            return ExitCode::USAGE;
        }

        $user = ctype_digit($this->user)
            ? Craft::$app->getUsers()->getUserById((int)$this->user)
            : Craft::$app->getUsers()->getUserByUsernameOrEmail($this->user);

        if (!$user || !$user->active || $user->suspended || $user->locked) {
            $this->stderr("The selected user is not permitted to use Craft MCP.\n");
            return ExitCode::NOPERM;
        }

        Craft::$app->getUser()->setIdentity($user);
        return Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport(logger: new \craft\mcp\Logger()));
    }
}
