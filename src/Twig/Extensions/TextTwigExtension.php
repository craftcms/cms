<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Stringable;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TextTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('ascii', Str::ascii(...)),
            new TwigFilter('camel', $this->camelFilter(...)),
            new TwigFilter('encenc', $this->encencFilter(...)),
            new TwigFilter('hash', $this->hashFilter(...)),
            new TwigFilter('kebab', $this->kebabFilter(...)),
            new TwigFilter('lcfirst', $this->lcfirstFilter(...)),
            new TwigFilter('pascal', $this->pascalFilter(...)),
            new TwigFilter('replace', $this->replaceFilter(...)),
            new TwigFilter('snake', $this->snakeFilter(...)),
            new TwigFilter('truncate', $this->truncateFilter(...)),
            new TwigFilter('widont', $this->widontFilter(...), ['is_safe' => ['html']]),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('randomString', Str::random(...)),
            new TwigFunction('uuid', Str::uuid(...)),
            new TwigFunction('uuid7', Str::uuid7(...)),
        ];
    }

    public function truncateFilter(string $string, int $length, string $suffix = '…', bool $splitSingleWord = true): string
    {
        if ($string === '' || $length <= 0) {
            return $string;
        }

        $length -= mb_strlen($suffix);

        return Str::limit($string, $length, $suffix, preserveWords: true);
    }

    public function lcfirstFilter(mixed $string): string
    {
        return Str::lcfirst((string) $string);
    }

    public function kebabFilter(mixed $string, string $glue = '-', bool $lower = true, bool $removePunctuation = true): string
    {
        return str($string)
            ->when($removePunctuation, fn (Stringable $s) => $s->replace(['.', '_', '-'], ' '))
            ->snake($glue)
            ->when($lower, fn (Stringable $s) => $s->lower())
            ->value();
    }

    public function camelFilter(mixed $string): string
    {
        return Str::camel((string) $string);
    }

    public function pascalFilter(mixed $string): string
    {
        return Str::pascal((string) $string);
    }

    public function snakeFilter(mixed $string): string
    {
        return Str::snake((string) $string);
    }

    public function encencFilter(mixed $str): string
    {
        return Str::encenc((string) $str);
    }

    public function hashFilter(string $data, ?string $algo = null): string
    {
        if ($algo === null) {
            return Crypt::encrypt($data);
        }

        return hash($algo, $data);
    }

    public function widontFilter(string $string): string
    {
        return Html::widont($string);
    }

    public function replaceFilter(mixed $str, mixed $search, mixed $replace = null, ?bool $regex = null): mixed
    {
        if (! is_string($str)) {
            return $str;
        }

        if (is_array($search)) {
            if (
                $regex === false ||
                (
                    $regex === null &&
                    Collection::make($search)->keys()->doesntContain(fn (string $pattern) => $this->isRegex($pattern))
                )
            ) {
                return strtr($str, $search);
            }
        } else {
            $search = [$search => $replace];
        }

        foreach ($search as $s => $r) {
            if ($regex ?? $this->isRegex((string) $s)) {
                $str = preg_replace((string) $s, (string) $r, (string) $str);
            } else {
                $str = str_replace((string) $s, (string) $r, $str);
            }
        }

        return $str;
    }

    private function isRegex(string $str): bool
    {
        if (! preg_match('/^\/([^\r\n]+)\/([imsxADSUXJun]*)$/', $str, $match)) {
            return false;
        }

        if (preg_match('/(?<!\\\)\//', $match[1])) {
            return false;
        }

        return true;
    }
}
