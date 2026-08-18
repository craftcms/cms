<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use LogicException;

use function CraftCms\Cms\t;

/**
 * @mixin PluginInterface
 *
 * @internal
 */
trait HasSettings
{
    /** @var bool Whether the plugin has a settings page in the control panel */
    public bool $hasCpSettings = false;

    /**
     * @var bool Whether the plugin supports a read-only settings page in the control panel, which
     *           can be shown when admin changes are disallowed.
     */
    public bool $hasReadOnlyCpSettings = false;

    /**
     * @var Validatable|bool|null The model used to store the plugin’s settings
     *
     * @see getSettings()
     */
    private bool|null|Validatable $settings = null;

    public function getSettings(): ?Validatable
    {
        if (! isset($this->settings)) {
            $this->settings = $this->createSettingsModel() ?: false;
        }

        return $this->settings ?: null;
    }

    /** @param array<string, mixed> $settings */
    public function setSettings(array $settings): void
    {
        if (($model = $this->getSettings()) === null) {
            Log::warning('Attempting to set settings on a plugin that doesn\'t have settings: '.$this->handle);

            return;
        }

        $model->setAttributes($settings);
    }

    /**
     * Override this to return a custom FormRequest class for plugin settings saves.
     *
     * Returning `null` keeps the default behavior, where only the settings model's
     * validation rules are applied.
     *
     * @return class-string<FormRequest>
     */
    public function getSettingsRequestClass(): ?string
    {
        return null;
    }

    public function getSettingsResponse(): mixed
    {
        return $this->settingsResponse(false);
    }

    public function getReadOnlySettingsResponse(): mixed
    {
        return $this->settingsResponse(true);
    }

    private function settingsResponse(bool $readOnly): mixed
    {
        $settings = $this->getSettings();

        if (! $readOnly && $settings === null) {
            throw new LogicException("Plugin [{$this->handle}] must provide a settings model when using the standard editable settings response.");
        }

        $context = new FormContext(
            namespace: 'settings',
            values: ['settings' => $settings?->validationData() ?? []],
            errors: $settings?->errors()->getMessages() ?? [],
            mode: $readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
        );
        $form = $this->settingsForm($context);

        if ($form === null) {
            throw new LogicException("Plugin [{$this->handle}] must return a Form from settingsForm() when using the standard settings response.");
        }

        return new CpScreenResponse()
            ->title($this->name)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Plugins'), 'settings/plugins')
            ->redirectUrl('settings')
            ->inertiaPage('Form', [
                'form' => app(FormResolver::class)->resolve($form, $context),
                'submit' => [
                    'method' => 'post',
                    'url' => Url::cpUrl("settings/plugins/{$this->handle}"),
                ],
            ]);
    }

    public function beforeSaveSettings(): bool
    {
        return true;
    }

    public function afterSaveSettings(): void
    {
        // carry on
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?Validatable
    {
        return null;
    }

    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        return null;
    }
}
