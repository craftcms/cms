<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\Update\Maintenance;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private GeneralConfig $generalConfig,
        private UiResolver $uiResolver,
        private Maintenance $maintenance,
    ) {}

    public function index(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('General Settings'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(Url::cpUrl('settings')),
                new ActionItem()->label(t('General Settings')),
            ])
            ->redirectUrl('settings')
            ->ui($this->systemSettingsUi(), props: [
                'readOnly' => false,
                'submit' => [
                    'method' => 'post',
                    'url' => action([self::class, 'store']),
                ],
            ]);
    }

    public function store(Request $request): Response
    {
        $rules = [
            'maintenanceMode' => ['required', 'boolean'],
        ];

        if ($this->generalConfig->allowAdminChanges) {
            $rules += [
                'name' => [new EnvValueRule(['required', 'string'])],
                'retryDuration' => ['nullable', 'integer'],
                'timeZone' => [new EnvValueRule(['required', 'string', new TimezoneRule])],
            ];
        }

        $settings = $request->validate($rules);
        $systemSettings = $this->projectConfig->get('system') ?? [];

        if ($this->generalConfig->allowAdminChanges) {
            $systemSettings['name'] = $settings['name'];
            $systemSettings['retryDuration'] = $settings['retryDuration'] ?? null;
            $systemSettings['timeZone'] = $settings['timeZone'];
            $this->projectConfig->set('system', $systemSettings, 'Update system settings.');
        }

        $retryDuration = $systemSettings['retryDuration'] ?? null;
        $this->maintenance->set(
            $request->boolean('maintenanceMode'),
            $retryDuration === null ? null : (int) $retryDuration,
        );

        return $this->asSuccess(t('System settings saved.'));
    }

    private function systemSettingsUi(): UiPayload
    {
        $system = $this->projectConfig->get('system') ?? [];
        $timezoneOptions = $this->timezoneOptions();
        $settingsMode = $this->generalConfig->allowAdminChanges
            ? ControlMode::Editable
            : ControlMode::ReadOnly;

        $ui = Ui::make([
            Field::make(t('System Name'), Text::make('name')
                ->mode($settingsMode)
                ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers()))
                ->required()
                ->tip(sprintf(
                    '%s [%s](%s)',
                    t('Type <kbd>$</kbd> to choose an environment variable.'),
                    t('Learn more'),
                    'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                )),
            Field::make(t('Maintenance Mode'), Lightswitch::make('maintenanceMode'))
                ->instructions(t('When enabled, site requests will return a service unavailable response and queued jobs will pause.')),
            Field::make(t('Retry Duration'), Number::make('retryDuration')
                ->mode($settingsMode)
                ->size(4))
                ->instructions(t('The number of seconds that the Retry-After HTTP header should be set to for maintenance mode responses.')),
            Field::make(t('Time Zone'), Combobox::make('timeZone')
                ->mode($settingsMode)
                ->options($timezoneOptions)
                ->showAllOnEmpty())
                ->required()
                ->tip(t('This can be set to an environment variable with a value of a [supported time zone]({url}).', [
                    'url' => 'https://www.php.net/manual/en/timezones.php',
                ])),
        ]);

        return $this->uiResolver->resolve($ui, new UiContext(
            values: [
                ...$system,
                'maintenanceMode' => $this->maintenance->isEnabled(),
            ],
        ));
    }

    /** @return list<array<string, mixed>> */
    private function timezoneOptions(): array
    {
        $options = SelectOptions::getTimeZoneOptions();

        return array_merge($options, SelectOptions::getEnvOptions(array_column($options, 'value')));
    }
}
