<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Cp\FormDefinitions\Elements;

use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\View\HtmlFragment;

class LegacySettings extends FormElement
{
    private function __construct(private readonly HtmlFragment $fragment)
    {
        parent::__construct();
    }

    public static function make(HtmlFragment $fragment): self
    {
        return new self($fragment);
    }

    public static function type(): string
    {
        return 'yii2-adapter:legacy-settings';
    }

    public static function isContainer(): bool
    {
        return true;
    }

    protected function props(): array
    {
        return ['fragment' => $this->fragment->toArray()];
    }
}
