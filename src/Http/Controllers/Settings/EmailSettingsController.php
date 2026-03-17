<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Email\Actions\SendTestMailAction;
use CraftCms\Cms\Http\Requests\EmailSettingsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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

    public function index(GeneralConfig $generalConfig): Response
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

        return Inertia::render('SettingsEmailPage', [
            'emailConfig' => $this->projectConfig->get('email') ?? [],
            'mailerOptions' => $this->getMailerOptions(),
            'envSuggestions' => SelectOptions::getEnvSuggestions(),
            'templateSuggestions' => SelectOptions::getTemplateSuggestions(),
            'sites' => $sites,
            'crumbs' => [
                ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
                ['label' => t('Email')],
            ],
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'saveUrl' => route('craft.cp.settings.email.store'),
            'testUrl' => route('craft.cp.settings.email.test'),
            'defaultToEmail' => auth()->user()->email,
        ]);
    }

    public function store(EmailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $emailSettings = array_filter([
            'fromEmail' => $validated['fromEmail'] ?? null,
            'fromName' => $validated['fromName'] ?? null,
            'replyToEmail' => $validated['replyToEmail'] ?? null,
            'mailer' => $validated['mailer'] ?? null,
            'template' => $validated['template'] ?? null,
        ]);

        $siteOverrides = array_filter(array_map(
            array_filter(...),
            $request->input('siteOverrides', []),
        ));

        if (! empty($siteOverrides)) {
            $emailSettings['siteOverrides'] = $siteOverrides;
        }

        $this->projectConfig->set('email', $emailSettings, 'Update email settings.');

        return back()->with('success', t('Email settings saved.'));
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
            ['value' => null, 'label' => t('Default ({mailer})', ['mailer' => $default])],
        ];

        foreach (array_keys($mailers) as $name) {
            $options[] = ['value' => $name, 'label' => $name];
        }

        return $options;
    }
}
