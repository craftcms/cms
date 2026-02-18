<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Craft;
use craft\web\Controller;
use CraftCms\Cms\Component\Concerns\HasComponentEvents;
use CraftCms\Cms\Component\Events\ComponentEvent;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use yii\web\Response;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasSettings
{
    use HasComponentEvents;

    /** @var bool Whether the plugin has a settings page in the control panel */
    public bool $hasCpSettings = false;

    /**
     * @var bool Whether the plugin supports a read-only settings page in the control panel, which
     *           can be shown when admin changes are disallowed.
     */
    public bool $hasReadOnlyCpSettings = false;

    /**
     * @event ComponentEvent The event that is triggered before the plugin’s settings are saved.
     *
     * You may set {@see ComponentEvent::$isValid} to `false` to prevent the plugin’s settings from saving.
     */
    public const string EVENT_BEFORE_SAVE_SETTINGS = 'beforeSaveSettings';

    /**
     * @event ComponentEvent The event that is triggered after the plugin’s settings are saved.
     */
    public const string EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

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

    public function setSettings(array $settings): void
    {
        if (($model = $this->getSettings()) === null) {
            Log::warning('Attempting to set settings on a plugin that doesn\'t have settings: '.$this->handle);

            return;
        }

        $model->setAttributes($settings);
    }

    public function getSettingsResponse(): mixed
    {
        return $this->settingsResponse(false);
    }

    public function getReadOnlySettingsResponse(): mixed
    {
        return $this->settingsResponse(true);
    }

    private function settingsResponse(bool $readOnly): Response
    {
        $settingsHtml = InputNamespace::namespaceInputs(function () use ($readOnly) {
            if ($readOnly) {
                // Just return the settings HTML with disabled inputs by default
                return (string) Html::disableInputs(fn () => $this->settingsHtml());
            }

            return (string) $this->settingsHtml();
        }, 'settings');

        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('settings/plugins/_settings.twig', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
            'readOnly' => $readOnly,
        ]);
    }

    public function beforeSaveSettings(): bool
    {
        event(self::componentEventName(self::EVENT_BEFORE_SAVE_SETTINGS), $event = new ComponentEvent($this));

        return $event->isValid;
    }

    public function afterSaveSettings(): void
    {
        event(self::componentEventName(self::EVENT_AFTER_SAVE_SETTINGS), new ComponentEvent($this));
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?Validatable
    {
        return null;
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content block on the settings page.
     *
     * @return string|null The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        return null;
    }
}
