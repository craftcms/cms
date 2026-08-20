<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private GeneralConfig $generalConfig,
        private FormResolver $formResolver,
    ) {}

    public function index(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('General Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('General Settings')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('Form', [
                'form' => $this->systemSettingsForm(),
                'submit' => [
                    'method' => 'post',
                    'url' => action([self::class, 'store']),
                ],
            ]);
    }

    public function store(Request $request): Response
    {
        $settings = $request->validate([
            'name' => [new EnvValueRule(['required', 'string'])],
            'live' => [new EnvValueRule(['required', 'boolean'])],
            'retryDuration' => ['nullable', 'integer'],
            'timeZone' => [new EnvValueRule(['required', 'string', new TimezoneRule])],
        ]);

        $systemSettings = $this->projectConfig->get('system') ?? [];
        $systemSettings['name'] = $settings['name'];
        $systemSettings['live'] = $settings['live'];
        $systemSettings['retryDuration'] = $settings['retryDuration'] ?? null;
        $systemSettings['timeZone'] = $settings['timeZone'];
        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return $this->asSuccess(t('System settings saved.'));
    }

    private function systemSettingsForm(): FormPayload
    {
        $system = $this->projectConfig->get('system') ?? [];
        $system['live'] = match ($system['live'] ?? null) {
            true => '1',
            false => '0',
            default => $system['live'] ?? '',
        };
        $timezoneOptions = $this->timezoneOptions();
        $statusOptions = $this->statusOptions();

        $form = Form::make([
            Field::make(t('System Name'), Text::make('name')
                ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers()))
                ->required()
                ->tip(sprintf(
                    '%s [%s](%s)',
                    t('Type `$` to choose an environment variable.'),
                    t('Learn more'),
                    'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                )),
            Field::make(t('System Status'), Combobox::make('live')
                ->options([
                    [
                        'value' => '1',
                        'label' => t('Online'),
                        'data' => ['indicator' => ['variant' => 'success']],
                    ],
                    [
                        'value' => '0',
                        'label' => t('Offline'),
                        'data' => ['indicator' => ['variant' => 'empty']],
                    ],
                    ...$statusOptions,
                ])
                ->showAllOnEmpty())
                ->required()
                ->tip(t('This can be set to an environment variable with a boolean value ({examples})', [
                    'examples' => '`yes`/`no`/`true`/`false`/`on`/`off`/`0`/`1`',
                ])),
            Field::make(t('Retry Duration'), Number::make('retryDuration')->size(4))
                ->instructions(t('The number of seconds that the Retry-After HTTP header should be set to for 503 responses when the system is offline.')),
            Field::make(t('Time Zone'), Combobox::make('timeZone')
                ->options($timezoneOptions)
                ->showAllOnEmpty())
                ->required()
                ->tip(t('This can be set to an environment variable with a value of a [supported time zone]({url}).', [
                    'url' => 'https://www.php.net/manual/en/timezones.php',
                ])),
        ]);

        return $this->formResolver->resolve($form, new FormContext(
            values: $system,
            mode: $this->generalConfig->allowAdminChanges ? ControlMode::Editable : ControlMode::ReadOnly,
        ));
    }

    /** @return list<array<string, mixed>> */
    private function timezoneOptions(): array
    {
        $options = SelectOptions::getTimeZoneOptions();

        return array_merge($options, SelectOptions::getEnvOptions(array_column($options, 'value')));
    }

    /** @return list<array<string, mixed>> */
    private function statusOptions(): array
    {
        $groups = SelectOptions::getBooleanEnvOptions();
        $groups[0]['options'] = $groups[0]['options']
            ->map(function (array $option): array {
                $online = $option['data']['boolean'] === '1';

                return [
                    ...$option,
                    'data' => [
                        ...$option['data'],
                        'hint' => $online ? t('Online') : t('Offline'),
                        'indicator' => [
                            'variant' => $online ? 'success' : 'empty',
                        ],
                    ],
                ];
            })
            ->all();

        return $groups;
    }
}
