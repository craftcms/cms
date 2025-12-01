<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Actions\GetImpersonationUrlAction;
use CraftCms\Cms\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Concerns\Colors;

use function Laravel\Prompts\info;

final class ImpersonateCommand extends Command implements PromptsForMissingInput
{
    use Colors;
    use CraftCommand;
    use PromptsForMissingUser;

    protected $signature = 'craft:users:impersonate {user}';

    protected $description = 'Generates a URL to impersonate a user.';

    protected $aliases = ['users/impersonate'];

    public function handle(GetImpersonationUrlAction $getImpersonationUrlAction): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $url = $getImpersonationUrlAction(User::findOrFail($user->id));

        if ($url === false) {
            $this->components->error('Unable to create the impersonation token.');

            return self::FAILURE;
        }

        info("Impersonation URL for “{$user->username}”: ".$this->cyan($url).' (Expires in one hour)');

        return self::SUCCESS;
    }
}
