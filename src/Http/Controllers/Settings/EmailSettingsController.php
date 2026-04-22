<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Email\Actions\SendTestMailAction;
use CraftCms\Cms\Http\Requests\EmailSettingsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

use function CraftCms\Cms\t;

readonly class EmailSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private Sites $sites,
    ) {}

    public function index(GeneralConfig $generalConfig): CpScreenResponse
    {
        $sites = [];
        if ($this->sites->isMultiSite()) {
            foreach ($this->sites->getAllSites() as $site) {
                $sites[] = [
                    'uid' => $site->uid,
                    'name' => $site->getUiLabel(),
                ];
            }
        }

        return new CpScreenResponse()
            ->title(t('Email Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
                ['label' => t('Email')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('SettingsEmailPage', [
                'emailConfig' => $this->projectConfig->get('email') ?? [],
                'mailerOptions' => $this->getMailerOptions(),
                'envSuggestions' => SelectOptions::getEnvSuggestions(),
                'templateSuggestions' => SelectOptions::getTemplateSuggestions(),
                'sites' => $sites,
                'defaultToEmail' => auth()->user()->email,
            ]);
    }

    public function store(EmailSettingsRequest $request): SymfonyResponse
    {
        $validated = $request->validated();

        $emailSettings = array_filter([
            'fromEmail' => $validated['fromEmail'] ?? null,
            'fromName' => $validated['fromName'] ?? null,
            'replyToEmail' => $validated['replyToEmail'] ?? null,
            'template' => $validated['template'] ?? null,
        ]);

        /**
         * When the mailer is explicitly set to null, the default at the time
         * of sending will be used, so we need this explicitly set to `null`
         * */
        $emailSettings['mailer'] = $validated['mailer'] ?? null;

        $siteOverrides = array_filter(array_map(
            array_filter(...),
            $validated['siteOverrides'] ?? [],
        ));

        if (! empty($siteOverrides)) {
            $emailSettings['siteOverrides'] = $siteOverrides;
        }

        $this->projectConfig->set('email', $emailSettings, 'Update email settings.');

        return $this->asSuccess(t('Email settings saved.'));
    }

    public function test(Request $request, SendTestMailAction $sendTestMail): SymfonyResponse|RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email:strict'],
        ]);

        try {
            $sendTestMail->handle($data['to']);
        } catch (Throwable $exception) {
            return back()->withErrors(['to' => $exception->getMessage()]);
        }

        return $this->asSuccess(t('Email sent successfully! Check your inbox.'));
    }

    /**
     * @return array<int, array{value: string|null, label: string}>
     */
    private function getMailerOptions(): array
    {
        $mailers = config('mail.mailers', []);
        $default = config('mail.default', 'smtp');

        $options = [
            ['value' => '', 'label' => t('Default ({mailer})', ['mailer' => $default])],
        ];

        foreach (array_keys($mailers) as $name) {
            $options[] = ['value' => $name, 'label' => $name];
        }

        return $options;
    }
}
