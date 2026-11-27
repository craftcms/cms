<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use Closure;
use CraftCms\Cms\Http\ResponseHeaders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Str;
use Livewire\Drawer\Utils;
use Livewire\Features\SupportPageComponents\PageComponentConfig;
use Livewire\Features\SupportPageComponents\SupportPageComponents;
use Livewire\Livewire;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mounts Livewire components with the variables Craft’s site routes give them.
 *
 * @since 6.0.0
 */
class LivewireComponents
{
    /**
     * The Livewire namespace for single-file components in the site templates folder,
     * such as `craft::news._entry` for `news/_entry.blade.php`.
     */
    public const string NAMESPACE = 'craft';

    /**
     * Returns the Livewire name of a site template file, or `null` if it isn’t a Livewire component.
     */
    public function nameForTemplate(string $file): ?string
    {
        if (! str_ends_with($file, '.blade.php')) {
            return null;
        }

        $root = rtrim(realpath(Path::siteTemplates()) ?: Path::siteTemplates(), '/\\').DIRECTORY_SEPARATOR;
        $file = realpath($file) ?: $file;

        if (! str_starts_with($file, $root)) {
            return null;
        }

        $name = self::NAMESPACE.'::'.str_replace(['/', '\\'], '.', Str::substr($file, Str::length($root), -Str::length('.blade.php')));

        // Only matches files written as components, not every Blade template in the folder.
        return Livewire::exists($name) ? $name : null;
    }

    /**
     * Mounts a component, passing only the variables it declares.
     *
     * @param  array<string, mixed>  $variables
     */
    public function mount(string $name, array $variables): string
    {
        return Livewire::mount($name, $this->params($name, $variables));
    }

    /**
     * Mounts a component as a full page, in its layout.
     *
     * @param  array<string, mixed>  $variables
     */
    public function mountPage(string $name, array $variables): Response
    {
        [$html, $layout] = $this->renderPage($name, $variables);
        $response = response($html);

        if (is_callable($layout->response)) {
            ($layout->response)($response);
        }

        return $response;
    }

    /**
     * Mounts a component as a full page, in its layout, for a page template.
     *
     * The layout’s response callback is queued with {@see ResponseHeaders}, which applies it to the page’s
     * response, so the component can still set its status code, headers, and cookies.
     *
     * @param  array<string, mixed>  $variables
     */
    public function renderPageTemplate(string $name, array $variables): string
    {
        [$html, $layout] = $this->renderPage($name, $variables);

        if (is_callable($layout->response)) {
            app(ResponseHeaders::class)->modify(Closure::fromCallable($layout->response));
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{string, PageComponentConfig}
     */
    private function renderPage(string $name, array $variables): array
    {
        $html = '';

        $layout = SupportPageComponents::interceptTheRenderOfTheComponentAndRetreiveTheLayoutConfiguration(
            function () use (&$html, $name, $variables): void {
                $html = $this->mount($name, $variables);
            },
        ) ?: new PageComponentConfig;

        $layout->normalizeViewNameAndParamsForBladeComponents();

        return [SupportPageComponents::renderContentsIntoLayout($html, $layout), $layout];
    }

    /**
     * Keeps the variables the component asks for as public properties or `mount()` arguments.
     * Livewire would add any others to the component’s root element as HTML attributes.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function params(string $name, array $variables): array
    {
        $component = Livewire::new($name);
        $accepted = array_keys(Utils::getPublicPropertiesDefinedOnSubclass($component));

        if (method_exists($component, 'mount')) {
            $accepted = [
                ...$accepted,
                ...array_map(
                    fn (ReflectionParameter $parameter): string => $parameter->getName(),
                    new ReflectionMethod($component, 'mount')->getParameters(),
                ),
            ];
        }

        return array_intersect_key($variables, array_flip($accepted));
    }
}
