<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\User\Actions\GetImpersonationUrlAction;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Concerns\Colors;

use function Laravel\Prompts\info;

final class ImpersonateCommand extends Command implements PromptsForMissingInput
{
    use Colors;
    use CraftCommand;
    use PromptsForMissingUser;

    #[\Override]
    protected $signature = 'craft:users:impersonate {user}';

    #[\Override]
    protected $description = 'Generates a URL to impersonate a user.';

    #[\Override]
    protected $aliases = ['users/impersonate'];

    public function handle(GetImpersonationUrlAction $getImpersonationUrlAction): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $url = $getImpersonationUrlAction($user);

        if ($url === false) {
            $this->components->error('Unable to create the impersonation token.');

            return self::FAILURE;
        }

        info("Impersonation URL for “{$user->username}”: ".$this->cyan($url).' (Expires in one hour)');

        return self::SUCCESS;
    }
}
