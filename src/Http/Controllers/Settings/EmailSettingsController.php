<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Email\Actions\SendTestMailAction;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Heading;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Http\Requests\EmailSettingsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/** @phpstan-import-type TextExpanderTrigger from HasTextExpander */
readonly class EmailSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private Sites $sites,
        private FormResolver $formResolver,
    ) {}

    public function index(GeneralConfig $generalConfig): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('Email Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Email')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('settings/Email', [
                'form' => $this->emailSettingsForm($generalConfig),
                'submit' => [
                    'method' => 'post',
                    'url' => action([self::class, 'store']),
                ],
                'defaultToEmail' => currentUser()?->asElement()->email,
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

    private function emailSettingsForm(GeneralConfig $generalConfig): FormPayload
    {
        $environmentOptions = SelectOptions::getEnvSuggestions();
        $environmentTextExpanderTriggers = SelectOptions::getEnvTextExpanderTriggers();
        $templateOptions = [
            ...SelectOptions::getTemplateSuggestions(),
            ...$environmentOptions,
        ];
        $environmentTip = sprintf(
            '%s [%s](%s)',
            t('Type `$` to choose an environment variable.'),
            t('Learn more'),
            'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
        );
        $form = Form::make([
            Field::make(t('System Email Address'), Text::make('fromEmail')
                ->textExpanderTriggers($environmentTextExpanderTriggers))
                ->instructions(t('The email address Craft CMS will use when sending email.'))
                ->required()
                ->tip($environmentTip),
            Field::make(t('Sender Name'), Text::make('fromName')
                ->textExpanderTriggers($environmentTextExpanderTriggers))
                ->instructions(t('The “From” name Craft CMS will use when sending email.'))
                ->required()
                ->tip($environmentTip),
            Field::make(t('Reply-To Address'), Text::make('replyToEmail')
                ->textExpanderTriggers($environmentTextExpanderTriggers))
                ->instructions(t('The Reply-To email address Craft CMS should use when sending email.'))
                ->tip($environmentTip),
            Field::make(t('HTML Email Template'), Combobox::make('template')
                ->options($templateOptions)
                ->showAllOnEmpty())
                ->instructions(t('The template Craft CMS will use for HTML emails. Leave blank to use the default template.'))
                ->tip($environmentTip),
        ])->when(
            $this->sites->isMultiSite(),
            fn (Form $form): Form => $form->add(
                Separator::make('site-overrides-separator'),
                Heading::make('site-overrides-heading', t('Site Overrides'))
                    ->description(t('Override the default email settings on a per-site basis. Blank values will use the defaults above.')),
                $this->siteOverridesTable($environmentTextExpanderTriggers, $templateOptions),
            ),
        )->add(
            Separator::make('mailer-separator'),
            Field::make(t('Mailer'), Combobox::make('mailer')
                ->options([
                    ...$this->getMailerOptions(),
                    ...$environmentOptions,
                ])
                ->showAllOnEmpty())
                ->instructions(t('How should Craft CMS send the emails?'))
                ->tip($environmentTip),
        );

        return $this->formResolver->resolve($form, new FormContext(
            values: $this->emailSettingsValues(),
            mode: $generalConfig->allowAdminChanges ? ControlMode::Editable : ControlMode::ReadOnly,
        ));
    }

    /**
     * @param  list<TextExpanderTrigger>  $environmentTextExpanderTriggers
     * @param  list<array<string, mixed>>  $templateOptions
     */
    private function siteOverridesTable(array $environmentTextExpanderTriggers, array $templateOptions): Field
    {
        return Field::make(control: Table::make('siteOverrides')
            ->keyed()
            ->columns([
                'site' => ['heading' => t('Site'), 'type' => 'heading'],
                'fromEmail' => [
                    'heading' => t('System Email Address'),
                    'type' => 'autosuggest',
                    'textExpanderTriggers' => $environmentTextExpanderTriggers,
                ],
                'fromName' => [
                    'heading' => t('Sender Name'),
                    'type' => 'autosuggest',
                    'textExpanderTriggers' => $environmentTextExpanderTriggers,
                ],
                'replyToEmail' => [
                    'heading' => t('Reply-To Address'),
                    'type' => 'autosuggest',
                    'textExpanderTriggers' => $environmentTextExpanderTriggers,
                ],
                'template' => [
                    'heading' => t('HTML Email Template'),
                    'type' => 'template',
                    'options' => $templateOptions,
                    'suggestEnvVars' => true,
                ],
            ]));
    }

    /** @return array<string, mixed> */
    private function emailSettingsValues(): array
    {
        $config = $this->projectConfig->get('email') ?? [];
        $siteOverrides = [];

        if ($this->sites->isMultiSite()) {
            foreach ($this->sites->getAllSites() as $site) {
                $override = $config['siteOverrides'][$site->uid] ?? [];
                $siteOverrides[$site->uid] = [
                    'site' => Html::encode($site->getUiLabel()),
                    'fromEmail' => $override['fromEmail'] ?? '',
                    'fromName' => $override['fromName'] ?? '',
                    'replyToEmail' => $override['replyToEmail'] ?? '',
                    'template' => $override['template'] ?? '',
                ];
            }
        }

        return [
            'fromEmail' => $config['fromEmail'] ?? '',
            'fromName' => $config['fromName'] ?? '',
            'replyToEmail' => $config['replyToEmail'] ?? '',
            'template' => $config['template'] ?? '',
            'siteOverrides' => $siteOverrides,
            'mailer' => $config['mailer'] ?? '',
        ];
    }

    /** @return list<array{value: string, label: string}> */
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
