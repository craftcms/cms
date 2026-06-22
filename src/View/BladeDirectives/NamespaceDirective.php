<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\BladeDirectives;

use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Context;
use Illuminate\View\Compilers\BladeCompiler;

class NamespaceDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftNamespace', fn (string $expression) => sprintf('<?php %s::start(%s); ?>', self::class, $expression));
        $blade->directive('endCraftNamespace', fn (string $expression = '') => '<?php echo '.self::class.'::end(); ?>');
    }

    public static function start(?string $namespace, bool $withClasses = false): void
    {
        $originalNamespace = InputNamespace::get();

        if ($namespace !== null && $namespace !== '') {
            InputNamespace::set(InputNamespace::namespaceInputName($namespace));
        }

        Context::pushHidden(self::class, [$namespace, $withClasses, $originalNamespace]);

        ob_start();
    }

    public static function end(): string
    {
        $body = (string) ob_get_clean();
        [$namespace, $withClasses, $originalNamespace] = Context::popHidden(self::class);

        InputNamespace::set($originalNamespace);

        if ($namespace === null || $namespace === '') {
            return $body;
        }

        return Html::namespaceHtml($body, $namespace, $withClasses);
    }
}
