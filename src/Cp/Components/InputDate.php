<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;
use Override;

class InputDate extends Input
{
    #[Override]
    protected string $type = 'date';

    protected ?string $baseName = null;

    protected ?string $locale = null;

    protected ?string $timezone = null;

    protected bool $outputLocaleParam = true;

    protected bool $outputTimezoneParam = true;

    #[Override]
    protected function tagName(): string
    {
        return 'craft-input-date';
    }

    #[Override]
    public function name(?string $name): static
    {
        $this->baseName = $name;

        return parent::name($name === null ? null : "{$name}[date]");
    }

    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function timezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function outputLocaleParam(bool $outputLocaleParam = true): static
    {
        $this->outputLocaleParam = $outputLocaleParam;

        return $this;
    }

    public function outputTimezoneParam(bool $outputTimezoneParam = true): static
    {
        $this->outputTimezoneParam = $outputTimezoneParam;

        return $this;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'class' => ['datewrapper'],
        ];
    }

    #[Override]
    protected function renderSlots(): string
    {
        return $this->metadataHtml().parent::renderSlots();
    }

    protected function metadataHtml(): string
    {
        if ($this->baseName === null) {
            return '';
        }

        return
            ($this->outputLocaleParam
                ? Html::hiddenInput("{$this->baseName}[locale]", $this->locale)
                : '').
            ($this->outputTimezoneParam
                ? Html::hiddenInput("{$this->baseName}[timezone]", $this->timezone)
                : '');
    }
}
