<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Console\CraftCommand;
use DateTime;
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

    public function handle(): int
    {
        if (! $user = $this->getUser()) {
            return self::FAILURE;
        }

        $token = Craft::$app->getTokens()->createToken([
            'users/impersonate-with-token', [
                'userId' => $user->id,
                'prevUserId' => $user->id,
            ],
        ], 1, new DateTime('+1 hour'));

        if (! $token) {
            $this->components->error('Unable to create the impersonation token.');

            return self::FAILURE;
        }

        $url = $user->can('accessCp') ? UrlHelper::cpUrl() : UrlHelper::siteUrl();
        $url = UrlHelper::urlWithToken($url, $token);

        info("Impersonation URL for “{$user->username}”: ".$this->cyan($url).' (Expires in one hour)');

        return self::SUCCESS;
    }
}
