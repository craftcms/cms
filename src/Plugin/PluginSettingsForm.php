<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use LogicException;

/** @internal */
readonly class PluginSettingsForm
{
    public function __construct(private FormResolver $formResolver) {}

    public function render(PluginInterface $plugin, bool $readOnly = false): FormPayload
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
    public function refresh(PluginInterface $plugin, array $values, array $scope): FormPayload
    {
        $settings = $plugin->getSettings()?->validationData() ?? [];
        $settings = $scope === ['settings']
            ? $values
            : data_set($settings, array_slice($scope, 1), $values);
        $plugin->setSettings($settings);

        return $this->resolve($plugin, $settings)->forScope($scope);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string|list<string>>  $errors
     */
    private function resolve(PluginInterface $plugin, array $values, array $errors = [], bool $readOnly = false): FormPayload
    {
        $context = new FormContext(
            namespace: 'settings',
            values: ['settings' => $values],
            errors: $errors,
            mode: $readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $readOnly,
        );
        $form = $plugin->settingsForm($context);

        if ($form === null) {
            throw new LogicException("Plugin [{$plugin->handle}] must return a Form from settingsForm().");
        }

        return $this->formResolver->resolve($form, $context);
    }
}
