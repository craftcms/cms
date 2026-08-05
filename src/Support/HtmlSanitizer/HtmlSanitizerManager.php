<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\HtmlSanitizer;

use Closure;
use CraftCms\Cms\Support\HtmlSanitizer\AttributeSanitizers\VideoEmbedUrlSanitizer;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use UnexpectedValueException;

#[Singleton]
class HtmlSanitizerManager extends Manager
{
    /** @var list<Closure> */
    private array $defaultCallbacks = [];

    public function getDefaultDriver(): string
    {
        return 'default';
    }

    /**
     * @param  string  $driver
     * @param  array<string, mixed>|Closure|HtmlSanitizerInterface  $definition
     */
    #[Override]
    public function extend($driver, array|Closure|HtmlSanitizerInterface $definition): static
    {
        $creator = $definition instanceof Closure
            ? $definition
            : static fn () => $definition;

        parent::extend($driver, $creator);

        return $this;
    }

    public function defaults(Closure $callback): static
    {
        $this->defaultCallbacks[] = $callback;

        return $this;
    }

    public function has(string $name): bool
    {
        if ($name === $this->getDefaultDriver()) {
            return true;
        }

        return array_key_exists($name, $this->customCreators);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_values(array_unique([
            $this->getDefaultDriver(),
            ...array_keys($this->customCreators),
        ]));
    }

    /** @return Collection<string, HtmlSanitizerInterface> */
    public function all(): Collection
    {
        return collect($this->names())
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
        return $this->driver($name);
    }

    /**
     * @param  string|null  $driver
     */
    #[Override]
    public function driver($driver = null): HtmlSanitizerInterface
    {
        return parent::driver($driver);
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

            if (! $resolvedConfig instanceof HtmlSanitizerConfig) {
                throw new UnexpectedValueException('HTML sanitizer default callbacks must return '.HtmlSanitizerConfig::class.'.');
            }

            $config = $resolvedConfig;
        }

        return $config;
    }

    #[Override]
    protected function createDriver($driver): HtmlSanitizerInterface
    {
        $definition = parent::createDriver($driver);

        if ($definition instanceof HtmlSanitizerInterface) {
            return $definition;
        }

        if (is_array($definition)) {
            return new HtmlSanitizer($this->configFromArray($definition));
        }

        throw new UnexpectedValueException("HTML sanitizer [$driver] must resolve to an array config or an instance of ".HtmlSanitizerInterface::class.'.');
    }

    protected function createDefaultDriver(): HtmlSanitizerInterface
    {
        return new HtmlSanitizer($this->defaultConfig());
    }

    /** @param array<string, mixed> $settings */
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
