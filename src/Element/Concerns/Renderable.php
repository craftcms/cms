<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Craft;
use craft\events\RenderElementEvent;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Twig\Markup;

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
    /**
     * @event RenderElementEvent The event that is triggered before an element is rendered.
     *
     * @since 5.7.5
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
     * use craft\events\RenderElementEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     Element::class,
     *     Element::EVENT_RENDER,
     *     function(RenderElementEvent $event) {
     *         $event->output = '…';
     *     }
     * );
     * ```
     */
    public const EVENT_RENDER = 'render';

    /**
     * {@inheritdoc}
     */
    public function render(array $variables = []): Markup
    {
        $templates = $this->partialTemplatePathCandidates();

        if ($refHandle = static::refHandle()) {
            $variables[$refHandle] = $this;
        }

        if ($this->hasEventHandlers(self::EVENT_RENDER)) {
            $event = new RenderElementEvent([
                'templates' => $templates,
                'variables' => $variables,
            ]);

            $this->trigger(self::EVENT_RENDER, $event);

            if (isset($event->output)) {
                return new Markup($event->output, 'UTF-8');
            }

            $templates = $event->templates;
            $variables = $event->variables;
        }

        if (! empty($templates)) {
            $view = Craft::$app->getView();
            foreach (Arr::sort($templates, 'priority') as $template) {
                if (! $view->doesTemplateExist($template['template'], View::TEMPLATE_MODE_SITE)) {
                    continue;
                }

                $output = $view->renderTemplate($template['template'], $variables, View::TEMPLATE_MODE_SITE);

                return new Markup($output, 'UTF-8');
            }
        }

        // fallback to the string representation of the element
        $output = Html::tag('p', Html::encode((string) $this));

        return new Markup($output, 'UTF-8');
    }

    /**
     * Returns the template paths to check when rendering the element’s partial template.
     *
     * @return array{template:string,priority:int}[]
     *
     * @since 5.8.0
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
