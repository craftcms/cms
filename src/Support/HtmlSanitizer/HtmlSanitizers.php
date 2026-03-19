<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\HtmlSanitizer;

use Closure;
use CraftCms\Cms\Support\HtmlSanitizer\AttributeSanitizers\VideoEmbedUrlSanitizer;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[Singleton]
class HtmlSanitizers
{
    /** @var array<string, Closure|HtmlSanitizerInterface> */
    private array $definitions = [];

    /** @var list<Closure> */
    private array $defaultCallbacks = [];

    /** @var array<string, HtmlSanitizerInterface> */
    private array $resolvedSanitizers = [];

    public function __construct()
    {
        $this->definitions['default'] = fn () => new HtmlSanitizer($this->defaultConfig());
    }

    public function register(string $name, Closure|HtmlSanitizerInterface $definition): void
    {
        $this->definitions[$name] = $definition;
        unset($this->resolvedSanitizers[$name]);
    }

    public function defaults(Closure $callback): void
    {
        $this->defaultCallbacks[] = $callback;
        unset($this->resolvedSanitizers['default']);
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function sanitize(string $html, HtmlSanitizerInterface|string|null $sanitizer = null): string
    {
        if (is_string($sanitizer)) {
            return $this->sanitizer($sanitizer)->sanitize($html);
        }

        if ($sanitizer instanceof HtmlSanitizerInterface) {
            return $sanitizer->sanitize($html);
        }

        return $this->sanitizer()->sanitize($html);
    }

    public function sanitizer(?string $name = null): HtmlSanitizerInterface
    {
        $name ??= 'default';

        if (isset($this->resolvedSanitizers[$name])) {
            return $this->resolvedSanitizers[$name];
        }

        if (! isset($this->definitions[$name])) {
            throw new InvalidArgumentException("Unknown HTML sanitizer [$name].");
        }

        $sanitizer = $this->resolveDefinition($this->definitions[$name]);

        return $this->resolvedSanitizers[$name] = $sanitizer;
    }

    public function defaultConfig(): HtmlSanitizerConfig
    {
        $config = new HtmlSanitizerConfig()
            ->allowSafeElements()
            ->allowStaticElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('data-oembed-url', ['div'])
            ->allowElement('oembed')
            ->allowAttribute('url', ['oembed'])
            ->withAttributeSanitizer(new VideoEmbedUrlSanitizer);

        foreach ($this->defaultCallbacks as $callback) {
            $resolvedConfig = $callback($config);

            if ($resolvedConfig instanceof HtmlSanitizerConfig) {
                $config = $resolvedConfig;
            }
        }

        return $config;
    }

    private function resolveDefinition(Closure|HtmlSanitizerInterface $definition): HtmlSanitizerInterface
    {
        $resolvedSanitizer = value($definition);

        if ($resolvedSanitizer instanceof HtmlSanitizerInterface) {
            return $resolvedSanitizer;
        }

        throw new InvalidArgumentException('HTML sanitizer definitions must resolve to HtmlSanitizerInterface instances.');
    }
}
