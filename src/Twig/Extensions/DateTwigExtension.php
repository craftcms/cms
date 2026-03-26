<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use DateInterval;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Override;
use Throwable;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DateTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('atom', $this->atomFilter(...), ['needs_environment' => true]),
            new TwigFilter('date', $this->dateFilter(...), ['needs_environment' => true]),
            new TwigFilter('datetime', $this->datetimeFilter(...), ['needs_environment' => true]),
            new TwigFilter('duration', DateTimeHelper::humanDuration(...)),
            new TwigFilter('httpdate', $this->httpdateFilter(...), ['needs_environment' => true]),
            new TwigFilter('rss', $this->rssFilter(...), ['needs_environment' => true]),
            new TwigFilter('time', $this->timeFilter(...), ['needs_environment' => true]),
            new TwigFilter('timestamp', $this->timestampFilter(...)),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('date', $this->dateFunction(...), ['needs_environment' => true]),
        ];
    }

    public function timestampFilter(mixed $value, ?string $format = null, bool $withPreposition = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return I18N::getFormatter()->asTimestamp($value, $format, $withPreposition);
        } catch (Throwable) {
            return $value;
        }
    }

    public function dateFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        if ($date instanceof DateInterval) {
            return $env->getExtension(CoreExtension::class)->formatDate($date, $format, $timezone);
        }

        $format = $this->normalizeFormat($format);

        return $this->formatWithLocale($env, $date, $format, $timezone, $locale, 'asDate');
    }

    public function atomFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return $env->getExtension(CoreExtension::class)->formatDate($date, DateTime::ATOM, $timezone);
    }

    public function rssFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return $env->getExtension(CoreExtension::class)->formatDate($date, DateTime::RSS, $timezone);
    }

    public function timeFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        $format = $this->normalizeFormat($format);

        return $this->formatWithLocale($env, $date, $format, $timezone, $locale, 'asTime');
    }

    public function datetimeFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        $format = $this->normalizeFormat($format);

        return $this->formatWithLocale($env, $date, $format, $timezone, $locale, 'asDatetime');
    }

    private function normalizeFormat(?string $format): ?string
    {
        if ($format === null || in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            return $format;
        }

        if (str_starts_with($format, 'icu:')) {
            return substr($format, 4);
        }

        return Str::start($format, 'php:');
    }

    private function formatWithLocale(
        TwigEnvironment $env,
        mixed $date,
        ?string $format,
        mixed $timezone,
        ?string $locale,
        string $method,
    ): string {
        $date = $env->getExtension(CoreExtension::class)->convertDate($date, $timezone);
        $formatter = $locale ? I18N::getLocaleById($locale)->getFormatter() : I18N::getFormatter();
        $originalTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->{$method}(DateTime::createFromInterface($date), $format);
        $formatter->timeZone = $originalTimeZone;

        return $formatted;
    }

    public function httpdateFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return $env->getExtension(CoreExtension::class)->formatDate($date, DateTime::RFC7231, $timezone);
    }

    public function dateFunction(TwigEnvironment $env, mixed $date = null, mixed $timezone = null): DateTimeInterface
    {
        if (is_array($date)) {
            $date = DateTimeHelper::toDateTime($date, false, false);
            if ($date === false) {
                throw new InvalidArgumentException('Invalid date passed to date() function');
            }
        }

        return $env->getExtension(CoreExtension::class)->convertDate($date, $timezone);
    }
}
