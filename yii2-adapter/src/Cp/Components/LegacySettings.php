<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Cp\Components;

use CraftCms\Cms\Cp\Components\FormContainer;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlFragment;
use InvalidArgumentException;
use Override;

class LegacySettings extends FormContainer
{
    private ?HtmlFragment $fragment = null;

    public static function make(?HtmlFragment $fragment = null): static
    {
        $component = parent::make();

        if ($fragment !== null) {
            $component->fragment($fragment);
        }

        return $component;
    }

    public static function formElementType(): string
    {
        return 'yii2-adapter:legacy-settings';
    }

    public function fragment(HtmlFragment $fragment): static
    {
        $this->fragment = $fragment;

        return $this;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-legacy-settings-island';
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function formElementProps(): array
    {
        return ['fragment' => $this->resolvedFragment('Form Definition')->toArray()];
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'data-fragment' => Json::encode($this->resolvedFragment('HTML')->toArray()),
        ];
    }

    private function resolvedFragment(string $output): HtmlFragment
    {
        return $this->fragment ?? throw new InvalidArgumentException(sprintf(
            '%s requires a fragment for %s output.',
            static::class,
            $output,
        ));
    }
}
