<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use LogicException;

/**
 * @internal
 *
 * @since 6.0.0
 */
readonly class PluginSettingsForm
{
    public function __construct(private UiResolver $formResolver) {}

    public function render(PluginInterface $plugin, bool $readOnly = false): UiPayload
    {
        $settings = $plugin->getSettings();

        if (! $readOnly && $settings === null) {
            throw new LogicException("Plugin [{$plugin->handle}] must provide a settings model when using the standard editable settings response.");
        }

        return $this->resolve(
            $plugin,
            $settings?->validationData() ?? [],
            $settings?->errors()->getMessages() ?? [],
            $readOnly,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $scope
     */
    public function refresh(PluginInterface $plugin, array $values, array $scope): UiPayload
    {
        $settings = $plugin->getSettings()?->validationData() ?? [];
        $settings = $scope === ['settings']
            ? $values
            : data_set($settings, array_slice($scope, 1), $values);

        return $this->resolve($plugin, $settings)->forScope($scope);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string|list<string>>  $errors
     */
    private function resolve(PluginInterface $plugin, array $values, array $errors = [], bool $readOnly = false): UiPayload
    {
        $context = new UiContext(
            namespace: 'settings',
            values: ['settings' => $values],
            errors: $errors,
            mode: $readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $readOnly,
        );
        $form = $plugin->settingsUi($context);

        if ($form === null) {
            throw new LogicException("Plugin [{$plugin->handle}] must return a Form from settingsUi().");
        }

        return $this->formResolver->resolve($form, $context);
    }
}
