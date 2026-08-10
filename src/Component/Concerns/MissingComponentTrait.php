<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\Component\MissingComponents;

use function CraftCms\Cms\template;

/** @phpstan-require-implements MissingComponentInterface */
trait MissingComponentTrait
{
    /**
     * @var class-string<ComponentInterface> The expected component class name.
     */
    public string $expectedType;

    /**
     * @var string|null The exception message that explains why the component class was invalid
     */
    public ?string $errorMessage = null;

    /**
     * @var array<string, mixed>|null The custom settings associated with the component, if it is savable
     */
    public ?array $settings = null;

    /**
     * Creates a new component of a given type based on this one’s properties.
     *
     * @template T of ComponentInterface
     *
     * @param  class-string<T>  $type  The component class that should be used as the fallback
     * @return T
     */
    public function createFallback(string $type): ComponentInterface
    {
        $config = $this->toArray();
        unset($config['expectedType'], $config['errorMessage'], $config['settings']);
        $config['type'] = $type;

        return ComponentHelper::createComponent($config, $type);
    }

    /**
     * Displays an error message (and possibly a plugin install button) in place of the normal component UI.
     */
    public function getPlaceholderHtml(): string
    {
        return template(
            '_special/missing-component',
            app(MissingComponents::class)->resolve($this->expectedType, $this->errorMessage),
        );
    }
}
