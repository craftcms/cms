<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Override;

class InputTime extends Input
{
    #[Override]
    protected string $type = 'time';

    protected ?string $baseName = null;

    protected ?string $locale = null;

    protected ?string $timezone = null;

    protected bool $outputLocaleParam = true;

    protected bool $outputTimezoneParam = true;

    /** @var list<array{0:string, 1:string}> */
    protected array $disabledTimeRanges = [];

    protected int $minuteIncrement = 30;

    protected bool $forceRoundTime = false;

    #[Override]
    protected function tagName(): string
    {
        return 'craft-input-time';
    }

    #[Override]
    public function name(?string $name): static
    {
        $this->baseName = $name;

        return parent::name($name === null ? null : "{$name}[time]");
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

    /** @param list<array{0:string, 1:string}> $disabledTimeRanges */
    public function disabledTimeRanges(array $disabledTimeRanges): static
    {
        $this->disabledTimeRanges = $disabledTimeRanges;

        return $this;
    }

    public function minuteIncrement(int $minuteIncrement): static
    {
        $this->minuteIncrement = $minuteIncrement;
        $this->step($minuteIncrement * 60);

        return $this;
    }

    public function forceRoundTime(bool $forceRoundTime = true): static
    {
        $this->forceRoundTime = $forceRoundTime;

        return $this;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'class' => ['timewrapper'],
            'minute-increment' => $this->minuteIncrement,
            'disabled-time-ranges' => $this->disabledTimeRanges !== [] ? Json::encode($this->disabledTimeRanges) : null,
            'force-round-time' => $this->forceRoundTime,
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
