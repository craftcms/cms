<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade\Directives;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use DateTimeInterface;
use Illuminate\View\Compilers\BladeCompiler;

class ResponseDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftRedirect', fn (string $expression) => sprintf('<?php %s::redirect(%s); ?>', self::class, $expression));
        $blade->directive('craftHeader', fn (string $expression) => sprintf('<?php %s::header(%s); ?>', self::class, $expression));
        $blade->directive('craftExpires', fn (string $expression) => sprintf('<?php %s::expires(%s); ?>', self::class, $expression));
        $blade->directive('craftExit', fn (string $expression = '') => sprintf('<?php %s::templateExit(%s); ?>', self::class, $expression));
    }

    public static function redirect(string $path, int $status = 302, ?string $notice = null, ?string $error = null): void
    {
        if ($error !== null) {
            Flash::error($error);
        }

        if ($notice !== null) {
            Flash::notice($notice);
        }

        redirect(Url::url($path), $status)->throwResponse();
    }

    public static function header(string $header): void
    {
        $parts = array_map(trim(...), explode(':', $header, 2));

        ResponseHeaders::add($parts[0], $parts[1] ?? '');
    }

    public static function expires(DateTimeInterface|string|int|null $expiration = null, ?string $unit = null): void
    {
        if (is_int($expiration)) {
            $duration = $unit === null
                ? $expiration
                : (int) now()->diffInSeconds(now()->add($expiration, $unit));
        } elseif ($expiration !== null) {
            $duration = DateTimeHelper::toDateTime($expiration)->getTimestamp() - now()->getTimestamp();
        } else {
            $duration = 0;
        }

        ResponseHeaders::setCache($duration);
    }

    public static function templateExit(?int $status = null, ?string $message = null): void
    {
        if ($status !== null) {
            abort($status, $message ?? '');
        }

        throw new TemplateExitException;
    }
}
