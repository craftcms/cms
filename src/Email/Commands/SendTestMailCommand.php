<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Email\Actions\SendTestMailAction;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Console\Command;
use Override;

use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class SendTestMailCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:mailer:test
        {--to= : Email address that should receive the test message.}
        {--site= : Site handle to test site-specific mail overrides.}
    ';

    #[Override]
    protected $description = 'Tests sending an email with the current mailer settings.';

    #[Override]
    protected $aliases = ['mailer/test'];

    public function handle(SendTestMailAction $sendTestMail): int
    {
        $siteId = $this->resolveSiteId();

        if ($siteId === false) {
            return self::FAILURE;
        }

        $to = $this->option('to');

        if (! is_string($to) || $to === '') {
            if (! $this->input->isInteractive()) {
                $this->components->error('Please provide a recipient with the --to option when running non-interactively.');

                return self::FAILURE;
            }

            $to = text(
                label: 'Which email address should the test email be sent to?',
                required: true,
                validate: ['email:strict'],
            );
        }

        $this->components->info("Sending a test email to {$to} with the following settings:");

        table(
            headers: ['Setting', 'Value'],
            rows: collect($sendTestMail->settings($siteId))
                ->map(fn (string $value, string $setting) => [$setting, $value])
                ->values()
                ->all(),
        );

        $sendTestMail->handle($to, $siteId);

        $this->components->success('Email sent successfully! Check your inbox.');

        return self::SUCCESS;
    }

    /**
     * Resolves the --site option to a site ID.
     *
     * @return int|null|false null if no site specified, false on error, int on success
     */
    private function resolveSiteId(): int|null|false
    {
        $siteHandle = $this->option('site');

        if (! is_string($siteHandle) || $siteHandle === '') {
            return null;
        }

        $site = Sites::getSiteByHandle($siteHandle);

        if ($site === null) {
            $this->components->error("Invalid site handle: {$siteHandle}");

            return false;
        }

        return $site->id;
    }
}
