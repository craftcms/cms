<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Lightswitch;
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
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private GeneralConfig $generalConfig,
        private FormResolver $formResolver,
        private MaintenanceMode $maintenanceMode,
    ) {}

    public function index(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('General Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('General Settings')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('Form', [
                'readOnly' => false,
                'form' => $this->systemSettingsForm(),
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

        if ($this->generalConfig->allowAdminChanges) {
            $systemSettings = $this->projectConfig->get('system') ?? [];
            $systemSettings['name'] = $settings['name'];
            $systemSettings['retryDuration'] = $settings['retryDuration'] ?? null;
            $systemSettings['timeZone'] = $settings['timeZone'];
            $this->projectConfig->set('system', $systemSettings, 'Update system settings.');
        }

        $this->setMaintenanceMode($request->boolean('maintenanceMode'));

        return $this->asSuccess(t('System settings saved.'));
    }

    private function systemSettingsForm(): FormPayload
    {
        $system = $this->projectConfig->get('system') ?? [];
        $timezoneOptions = $this->timezoneOptions();
        $settingsMode = $this->generalConfig->allowAdminChanges
            ? ControlMode::Editable
            : ControlMode::ReadOnly;

        $form = Form::make([
            Field::make(t('Maintenance Mode'), Lightswitch::make('maintenanceMode'))
                ->instructions(t('When enabled, site requests will return a service unavailable response and queued jobs will pause.')),
            Field::make(t('Retry Duration'), Number::make('retryDuration')
                ->mode($settingsMode)
                ->size(4))
                ->instructions(t('The number of seconds that the Retry-After HTTP header should be set to for maintenance mode responses.')),
            Field::make(t('System Name'), Text::make('name')
                ->mode($settingsMode)
                ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers()))
                ->required()
                ->tip(sprintf(
                    '%s [%s](%s)',
                    t('Type `$` to choose an environment variable.'),
                    t('Learn more'),
                    'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                )),
            Field::make(t('Time Zone'), Combobox::make('timeZone')
                ->mode($settingsMode)
                ->options($timezoneOptions)
                ->showAllOnEmpty())
                ->required()
                ->tip(t('This can be set to an environment variable with a value of a [supported time zone]({url}).', [
                    'url' => 'https://www.php.net/manual/en/timezones.php',
                ])),
        ]);

        return $this->formResolver->resolve($form, new FormContext(
            values: [
                ...$system,
                'maintenanceMode' => $this->maintenanceMode->active(),
            ],
        ));
    }

    private function setMaintenanceMode(bool $enabled): void
    {
        $active = $this->maintenanceMode->active();

        if (! $enabled) {
            if (! $active) {
                return;
            }

            $maintenanceFile = storage_path('framework/maintenance.php');

            if (File::isFile($maintenanceFile) && ! File::delete($maintenanceFile)) {
                throw new RuntimeException("Unable to delete the pre-rendered maintenance file: $maintenanceFile");
            }

            $this->maintenanceMode->deactivate();
            Event::dispatch(new MaintenanceModeDisabled);

            return;
        }

        $payload = $active ? $this->maintenanceMode->data() : [];
        $retryDuration = $this->projectConfig->get('system.retryDuration');

        if ($retryDuration === null) {
            unset($payload['retry']);
        } else {
            $payload['retry'] = (int) $retryDuration;
        }

        if ($active && $payload === $this->maintenanceMode->data()) {
            return;
        }

        $this->maintenanceMode->activate($payload);

        if (! $active) {
            Event::dispatch(new MaintenanceModeEnabled);
        }
    }

    /** @return list<array<string, mixed>> */
    private function timezoneOptions(): array
    {
        $options = SelectOptions::getTimeZoneOptions();

        return array_merge($options, SelectOptions::getEnvOptions(array_column($options, 'value')));
    }
}
