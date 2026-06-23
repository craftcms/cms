<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade\Directives;

use CraftCms\Cms\View\PageLifecycle;
use Illuminate\View\Compilers\BladeCompiler;

class PageLifecycleDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftHead', fn (string $expression = '') => sprintf('<?php app(\%s::class)->head(); ?>', PageLifecycle::class));
        $blade->directive('craftBeginBody', fn (string $expression = '') => sprintf('<?php app(\%s::class)->beginBody(); ?>', PageLifecycle::class));
        $blade->directive('craftEndBody', fn (string $expression = '') => sprintf('<?php app(\%s::class)->endBody(); ?>', PageLifecycle::class));
    }
}
