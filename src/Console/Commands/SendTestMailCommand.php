<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\SystemMessage\Actions\SendTestMailAction;
use CraftCms\Cms\Utility\Utilities\MailSettings;
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
    ';

    #[Override]
    protected $description = 'Tests sending an email with the current mailer settings.';

    #[Override]
    protected $aliases = ['mailer/test'];

    public function handle(SendTestMailAction $sendTestMail): int
    {
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
            rows: collect(MailSettings::settings())
                ->map(fn (string $value, string $setting) => [$setting, $value])
                ->values()
                ->all(),
        );

        $sendTestMail->handle($to);

        $this->components->success('Email sent successfully! Check your inbox.');

        return self::SUCCESS;
    }
}
