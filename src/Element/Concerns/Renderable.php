<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Events\Render;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\template;

/**
 * Renderable provides element rendering functionality.
 *
 * This trait handles methods related to rendering elements,
 * including render() and partial template path candidates.
 *
 * @internal
 */
trait Renderable
{
    public function render(array $variables = []): HtmlString
    {
        $templates = $this->partialTemplatePathCandidates();

        if ($refHandle = static::refHandle()) {
            $variables[$refHandle] = $this;
        }

        event($event = new Render(
            element: $this,
            templates: $templates,
            variables: $variables,
        ));

        if ($event->output !== null) {
            return new HtmlString($event->output);
        }

        $templates = $event->templates;
        $variables = $event->variables;

        if (! empty($templates)) {
            foreach (Arr::sort($templates, 'priority') as $template) {
                if (! app(TemplateResolver::class)->exists($template['template'], TemplateMode::Site)) {
                    continue;
                }

                $output = template($template['template'], $variables, templateMode: TemplateMode::Site);

                return new HtmlString($output);
            }
        }

        // fallback to the string representation of the element
        $output = Html::tag('p', Html::encode((string) $this));

        return new HtmlString($output);
    }

    /**
     * Returns the template paths to check when rendering the element’s partial template.
     *
     * @return array{template:string,priority:int}[]
     */
    protected function partialTemplatePathCandidates(): array
    {
        $refHandle = static::refHandle();

        if ($refHandle === null) {
            return [];
        }

        $templates = [];

        if ($providerHandle = $this->getFieldLayout()?->provider?->getHandle()) {
            $templates[] = [
                'template' => sprintf('%s/%s/%s', Cms::config()->partialTemplatesPath, $refHandle, $providerHandle),
                'priority' => 1,
            ];
        }

        $templates[] = [
            'template' => sprintf('%s/%s', Cms::config()->partialTemplatesPath, $refHandle),
            'priority' => 10,
        ];

        return $templates;
    }
}
