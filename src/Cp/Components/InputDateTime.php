<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

use function CraftCms\Cms\t;

class InputDateTime extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected ?string $dateValue = null;

    protected ?string $timeValue = null;

    protected ?string $timezone = null;

    protected ?string $locale = null;

    protected bool $showDate = true;

    protected bool $showTime = true;

    protected bool $showTimezone = false;

    protected ?string $min = null;

    protected ?string $max = null;

    protected ?string $minTime = null;

    protected ?string $maxTime = null;

    /** @var list<array{0:string, 1:string}> */
    protected array $disabledTimeRanges = [];

    protected int $minuteIncrement = 30;

    protected bool $forceRoundTime = false;

    protected bool $readOnly = false;

    protected bool $required = false;

    protected ?string $describedBy = null;

    protected function tagName(): string
    {
        return 'craft-input-date-time';
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function dateValue(?string $dateValue): static
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function timeValue(?string $timeValue): static
    {
        $this->timeValue = $timeValue;

        return $this;
    }

    public function timezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function showDate(bool $showDate = true): static
    {
        $this->showDate = $showDate;

        return $this;
    }

    public function showTime(bool $showTime = true): static
    {
        $this->showTime = $showTime;

        return $this;
    }

    public function showTimezone(bool $showTimezone = true): static
    {
        $this->showTimezone = $showTimezone;

        return $this;
    }

    public function min(?string $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(?string $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function minTime(?string $minTime): static
    {
        $this->minTime = $minTime;

        return $this;
    }

    public function maxTime(?string $maxTime): static
    {
        $this->maxTime = $maxTime;

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

        return $this;
    }

    public function forceRoundTime(bool $forceRoundTime = true): static
    {
        $this->forceRoundTime = $forceRoundTime;

        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'class' => ['datetimewrapper'],
            'name' => $this->name,
            'date-value' => $this->dateValue,
            'time-value' => $this->timeValue,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'show-date' => $this->showDate ? 'true' : 'false',
            'show-time' => $this->showTime ? 'true' : 'false',
            'show-timezone' => $this->showTimezone,
            'min' => $this->min,
            'max' => $this->max,
            'min-time' => $this->minTime,
            'max-time' => $this->maxTime,
            'minute-increment' => $this->minuteIncrement,
            'disabled-time-ranges' => $this->disabledTimeRanges !== [] ? Json::encode($this->disabledTimeRanges) : null,
            'force-round-time' => $this->forceRoundTime,
            'disabled' => $this->isDisabled(),
            'readonly' => $this->readOnly,
            'required' => $this->required,
            'described-by' => $this->describedBy,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $inputs = [];

        if ($this->showDate) {
            $inputs[] = InputDate::make()
                ->id($this->id === null ? null : "{$this->id}-date")
                ->name($this->name)
                ->value($this->dateValue)
                ->min($this->min)
                ->max($this->max)
                ->disabled($this->isDisabled())
                ->readOnly($this->readOnly)
                ->describedBy($this->describedBy)
                ->inputAttributes([
                    'required' => $this->required,
                    'aria' => ['label' => $this->showTime ? t('Date') : null],
                ])
                ->outputLocaleParam(false)
                ->outputTimezoneParam(false);
        }

        if ($this->showTime) {
            $inputs[] = InputTime::make()
                ->id($this->id === null ? null : "{$this->id}-time")
                ->name($this->name)
                ->value($this->timeValue)
                ->min($this->minTime)
                ->max($this->maxTime)
                ->minuteIncrement($this->minuteIncrement)
                ->disabledTimeRanges($this->disabledTimeRanges)
                ->forceRoundTime($this->forceRoundTime)
                ->disabled($this->isDisabled())
                ->readOnly($this->readOnly)
                ->describedBy($this->describedBy)
                ->inputAttributes([
                    'required' => $this->required,
                    'aria' => ['label' => $this->showDate ? t('Time') : null],
                ])
                ->outputLocaleParam(false)
                ->outputTimezoneParam(false);
        }

        if ($this->showTimezone) {
            $inputs[] = Input::make()
                ->name($this->name === null ? null : "{$this->name}[timezone]")
                ->value($this->timezone)
                ->disabled($this->isDisabled())
                ->readOnly($this->readOnly)
                ->describedBy($this->describedBy);
        } elseif ($this->name !== null) {
            $inputs[] = Html::hiddenInput("{$this->name}[timezone]", $this->timezone, [
                'data' => ['date-time-metadata' => 'timezone'],
            ]);
        }

        if ($this->name !== null && $this->locale !== null) {
            $inputs[] = Html::hiddenInput("{$this->name}[locale]", $this->locale, [
                'data' => ['date-time-metadata' => 'locale'],
            ]);
        }

        return implode('', array_map(strval(...), $inputs));
    }
}
