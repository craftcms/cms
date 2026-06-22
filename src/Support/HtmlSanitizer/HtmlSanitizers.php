<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\HtmlSanitizer;

use Closure;
use CraftCms\Cms\Support\HtmlSanitizer\AttributeSanitizers\VideoEmbedUrlSanitizer;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[Singleton]
class HtmlSanitizers
{
    /** @var array<string, array<string, mixed>|Closure|HtmlSanitizerInterface> */
    private array $definitions = [];

    /** @var list<Closure> */
    private array $defaultCallbacks = [];

    /** @var array<string, HtmlSanitizerInterface> */
    private array $resolvedSanitizers = [];

    public function __construct()
    {
        $this->definitions['default'] = fn () => new HtmlSanitizer($this->defaultConfig());
    }

    public function register(string $name, array|Closure|HtmlSanitizerInterface $definition): void
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

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->definitions);
    }

    /** @return Collection<string, HtmlSanitizerInterface> */
    public function all(): Collection
    {
        return collect($this->definitions)
            ->keys()
            ->mapWithKeys(fn (string $name) => [$name => $this->sanitizer($name)]);
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

    private function resolveDefinition(array|Closure|HtmlSanitizerInterface $definition): HtmlSanitizerInterface
    {
        $resolvedSanitizer = value($definition);

        if ($resolvedSanitizer instanceof HtmlSanitizerInterface) {
            return $resolvedSanitizer;
        }

        if (is_array($resolvedSanitizer)) {
            return new HtmlSanitizer($this->configFromArray($resolvedSanitizer));
        }

        throw new InvalidArgumentException('HTML sanitizer definitions must resolve to array configs or HtmlSanitizerInterface instances.');
    }

    private function configFromArray(array $settings): HtmlSanitizerConfig
    {
        $config = new HtmlSanitizerConfig;

        if (array_key_exists('default_action', $settings)) {
            $config = $config->defaultAction(HtmlSanitizerAction::from($settings['default_action']));
        }

        foreach (['allow_safe_elements' => 'allowSafeElements', 'allow_static_elements' => 'allowStaticElements'] as $key => $method) {
            if ($settings[$key] ?? false) {
                $config = $config->$method();
            }
        }

        foreach ($settings['allow_elements'] ?? [] as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (['block_elements' => 'blockElement', 'drop_elements' => 'dropElement'] as $key => $method) {
            foreach ($settings[$key] ?? [] as $element) {
                $config = $config->$method($element);
            }
        }

        foreach (['allow_attributes' => 'allowAttribute', 'drop_attributes' => 'dropAttribute'] as $key => $method) {
            foreach ($settings[$key] ?? [] as $attribute => $elements) {
                $config = $config->$method($attribute, $elements);
            }
        }

        foreach ($settings['force_attributes'] ?? [] as $element => $attributes) {
            foreach ($attributes as $attribute => $value) {
                $config = $config->forceAttribute($element, $attribute, $value);
            }
        }

        foreach ([
            'force_https_urls' => 'forceHttpsUrls',
            'allowed_link_schemes' => 'allowLinkSchemes',
            'allowed_link_hosts' => 'allowLinkHosts',
            'allow_relative_links' => 'allowRelativeLinks',
            'allowed_media_schemes' => 'allowMediaSchemes',
            'allowed_media_hosts' => 'allowMediaHosts',
            'allow_relative_medias' => 'allowRelativeMedias',
            'max_input_length' => 'withMaxInputLength',
        ] as $key => $method) {
            if (array_key_exists($key, $settings)) {
                $config = $config->$method($settings[$key]);
            }
        }

        foreach (['with_attribute_sanitizers' => 'withAttributeSanitizer', 'without_attribute_sanitizers' => 'withoutAttributeSanitizer'] as $key => $method) {
            foreach ($settings[$key] ?? [] as $sanitizer) {
                $config = $config->$method(is_string($sanitizer) ? app($sanitizer) : $sanitizer);
            }
        }

        return $config;
    }
}
