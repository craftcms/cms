<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Component\MissingComponents;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Arr;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Missing extends Control
{
    private string $provider;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return template('_special/missing-component', $control->props + ['formId' => null]);
    }

    public function provider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function component(): string
    {
        return 'craft:missing-control';
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $presentation = app(MissingComponents::class)->resolve(
            $this->provider,
            t('Form Control provider [{provider}] is unavailable.', [
                'provider' => $this->provider,
            ]),
        );

        return ['provider' => $this->provider] + Arr::only($presentation, [
            'error',
            'pluginName',
            'action',
        ]);
    }
}
