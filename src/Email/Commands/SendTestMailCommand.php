<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Email\Actions\SendTestMailAction;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Console\Command;
use Override;

use function Laravel\Prompts\select;
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
        $site = $this->resolveSite();

        if (! $site && ! $this->input->isInteractive()) {
            $site = Sites::getPrimarySite();
        }

        if (! $site) {
            $siteOptions = Sites::getAllSites()->keyBy('handle');
            $siteHandle = select(
                label: 'Choose a site',
                options: $siteOptions->map(fn ($s) => $s->name),
                default: Sites::getPrimarySite()->handle,
            );

            $site = $siteOptions->get($siteHandle);
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

        $this->components->info("Sending a test email to <options=underscore>{$to}</> with the following settings:");

        table(
            headers: ['Setting', 'Value'],
            rows: collect($sendTestMail->settings($site->id))
                ->map(fn (string $value, string $setting) => [$setting, $value])
                ->push(['Site', "{$site->name} (ID #{$site->id})"])
                ->values()
                ->all(),
        );

        $sendTestMail->handle($to, $site->id);

        $this->components->success('Email sent successfully! Check your inbox.');

        return self::SUCCESS;
    }

    /**
     * Resolves the requested site, defaulting to the primary site on single-site installs.
     *
     * @return Site|null Site if discoverable by handle, otherwise null on multi-site installs
     */
    private function resolveSite(): ?Site
    {
        if (! Sites::isMultiSite()) {
            return Sites::getPrimarySite();
        }

        $siteHandle = $this->option('site');

        if (! is_string($siteHandle) || $siteHandle === '') {
            return null;
        }

        return Sites::getSiteByHandle($siteHandle);
    }
}
