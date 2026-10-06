<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Mcp\ServerFactory;
use CraftCms\Cms\Mcp\StdioTransport;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Users;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;

use function CraftCms\Cms\craftAuth;

/**
 * Serves the authenticated MCP server over stdio, for MCP clients that launch Craft as a local process.
 *
 * Anyone who can run this command can already read Craft's environment and database, so it identifies the acting
 * user rather than authenticating them. Capabilities, permissions, and activity attribution then behave as they do
 * over HTTP.
 *
 * @since 6.0.0
 */
class ServeCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:mcp:serve
        {--user= : The ID, username, or email of the user to act as}';

    #[\Override]
    protected $description = 'Serves the Craft MCP server over stdio.';

    public function handle(Users $users, Request $request, Connection $db, ServerFactory $servers): int
    {
        ini_set('display_errors', 'stderr');

        $user = $this->user($users);

        if ($user === null) {
            return self::FAILURE;
        }

        craftAuth()->setUser($user);
        $request->setUserResolver(static fn (): User => $user);
        $db->useWriteConnectionWhenReading();

        $servers->stdio()->run($this->laravel->make(StdioTransport::class));

        return self::SUCCESS;
    }

    private function user(Users $users): ?User
    {
        $identifier = $this->option('user');

        if (! is_string($identifier) || $identifier === '') {
            return $this->reject('The --user option is required.');
        }

        $element = is_numeric($identifier)
            ? $users->getUserById((int) $identifier)
            : $users->getUserByUsernameOrEmail($identifier);

        if ($element === null) {
            return $this->reject("No user exists with the ID, username, or email [$identifier].");
        }

        if ($element->getStatus() !== UserElement::STATUS_ACTIVE) {
            return $this->reject("The user [$identifier] is not active.");
        }

        $user = User::query()->find($element->id);

        if (! $user instanceof User || ! $user->can('useCraftMcp')) {
            return $this->reject("The user [$identifier] does not have permission to use Craft MCP.");
        }

        return $user;
    }

    private function reject(string $message): null
    {
        $this->output->getErrorStyle()->error($message);

        return null;
    }
}
