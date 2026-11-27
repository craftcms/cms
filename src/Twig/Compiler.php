<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Support\Facades\Deprecator;
use Override;
use ReflectionClass;
use ReflectionMethod;
use Twig\Compiler as TwigCompiler;
use Twig\Extension\ExtensionInterface;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\TokenParser\TokenParserInterface;

/**
 * Compiles templates, and logs deprecation warnings for code that Twig 4 will reject, which Twig 3 doesn't report.
 *
 * @since 6.0.0
 */
class Compiler extends TwigCompiler
{
    /**
     * Methods that Twig 4 requires to declare a return type, by the interface or class that declares them.
     */
    private const array RETURN_TYPED_METHODS = [
        ExtensionInterface::class => ['getTokenParsers', 'getNodeVisitors', 'getFilters', 'getTests', 'getFunctions'],
        TokenParserInterface::class => ['parse', 'getTag'],
        Node::class => ['compile'],
    ];

    /**
     * @var array<class-string, true> Classes whose return types have been checked
     */
    private static array $checkedClasses = [];

    /**
     * @var list<Node> The nodes being compiled, innermost last
     */
    private array $nodes = [];

    #[Override]
    public function compile(Node $node, int $indentation = 0)
    {
        if ($node instanceof ModuleNode) {
            foreach ($this->getEnvironment()->getExtensions() as $extension) {
                $this->checkReturnTypes($extension);
            }
            foreach ($this->getEnvironment()->getTokenParsers() as $tokenParser) {
                $this->checkReturnTypes($tokenParser);
            }
        }

        return $this->compileNode($node, fn () => parent::compile($node, $indentation));
    }

    #[Override]
    public function subcompile(Node $node, bool $raw = true)
    {
        return $this->compileNode($node, fn () => parent::subcompile($node, $raw));
    }

    #[Override]
    public function raw(string $string)
    {
        $this->checkForOutputBuffering($string);

        return parent::raw($string);
    }

    #[Override]
    public function write(mixed ...$strings)
    {
        foreach ($strings as $string) {
            $this->checkForOutputBuffering((string) $string);
        }

        return parent::write(...$strings);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $compile
     * @return T
     */
    private function compileNode(Node $node, callable $compile): mixed
    {
        $this->checkReturnTypes($node);
        $this->nodes[] = $node;

        try {
            return $compile();
        } finally {
            array_pop($this->nodes);
        }
    }

    private function checkReturnTypes(object $object): void
    {
        if (isset(self::$checkedClasses[$object::class])) {
            return;
        }

        self::$checkedClasses[$object::class] = true;

        foreach (self::RETURN_TYPED_METHODS as $type => $methods) {
            if (! $object instanceof $type) {
                continue;
            }

            foreach ($methods as $name) {
                $method = new ReflectionMethod($object, $name);
                $class = $method->getDeclaringClass()->getName();

                if (! $method->hasReturnType() && ! $this->isTwigClass($class)) {
                    Deprecator::log(
                        sprintf('twig4-return-type:%s::%s', $this->storableClassName($class), $name),
                        sprintf('`%s::%s()` must declare a return type to be compatible with Twig 4.', $this->readableClassName($class), $name),
                        $method->getFileName() ?: null,
                        $method->getStartLine() ?: null,
                    );
                }
            }
        }
    }

    /**
     * Returns whether a class is part of Twig itself, judging by its file, as anonymous subclasses of Twig classes share their namespace.
     */
    private function isTwigClass(string $class): bool
    {
        $twigDir = dirname((string) new ReflectionClass(TwigCompiler::class)->getFileName()).DIRECTORY_SEPARATOR;

        return str_starts_with((string) new ReflectionClass($class)->getFileName(), $twigDir);
    }

    /**
     * Returns a class name without the NUL byte that anonymous class names contain, which PostgreSQL can't store.
     * Anonymous classes keep their file and line, so each one gets its own deprecation warning.
     */
    private function storableClassName(string $class): string
    {
        return str_replace("\0", '', $class);
    }

    /**
     * Returns a class name as PHP shows it, such as `Twig\Node\Node@anonymous`.
     */
    private function readableClassName(string $class): string
    {
        return strstr($class, "\0", true) ?: $class;
    }

    /**
     * Logs custom nodes that capture their output with `ob_start()`, which captures nothing on Twig 4, as Twig 4 yields output instead of echoing it.
     */
    private function checkForOutputBuffering(string $code): void
    {
        $node = end($this->nodes);

        if ($node === false || ! str_contains($code, 'ob_start(') || $this->isTwigClass($node::class)) {
            return;
        }

        Deprecator::log(
            sprintf('twig4-ob-start:%s', $this->storableClassName($node::class)),
            sprintf("`%s` captures output with `ob_start()`, which won't capture anything on Twig 4. Wrap the nodes it captures in `Twig\\Node\\CaptureNode` instead.", $this->readableClassName($node::class)),
            $node->getTemplateName(),
            $node->getTemplateLine() ?: null,
        );
    }
}
