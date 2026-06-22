<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\BladeDirectives;

use CraftCms\Cms\Support\Template;
use Illuminate\View\Compilers\BladeCompiler;

class PaginationDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftPaginate', fn (string $expression) => sprintf('<?php [$paginate, $paginatedItems] = \%s::paginateQuery(%s); ?>', Template::class, $expression));
    }
}
