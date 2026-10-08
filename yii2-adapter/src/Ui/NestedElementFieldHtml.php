<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Ui;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** Renders native nested controls with relative input names for legacy field callers. */
class NestedElementFieldHtml
{
    public function __construct(
        private readonly UiResolver $resolver,
        private readonly UiHtmlRenderer $renderer,
    ) {
    }

    /** @param array<string, list<string>> $errors */
    public function render(Control $control, string $inputId, string $inputName, ControlMode $mode, array $errors = []): string
    {
        $payload = $this->resolver->resolve(
            Ui::make([Field::make()->control($control)]),
            new UiContext(mode: $mode, errors: $errors),
        );
        $control = $payload->nodes[0]->control;

        $html = $this->rebase($this->renderer->render($payload), $control, [
            'id' => $inputId,
            'name' => $inputName,
        ], $this->renderer);

        return new Crawler($html)->filter('craft-entry-field-layout-ui[data-field-path], craft-nested-elements-control')->first()->outerHtml();
    }

    /**
     * Legacy fragments are captured before nested Ui scopes have been resolved.
     *
     * @param array<string, mixed> $attributes
     */
    public function rebase(string $html, ControlPayload $control, array $attributes, UiHtmlRenderer $renderer): string
    {
        if (!str_contains($html, '<craft-nested-elements-control') && !str_contains($html, 'data-field-path')) {
            return $html;
        }

        $crawler = new Crawler($html);
        foreach ($crawler->filter('craft-nested-elements-control') as $host) {
            if (!$host instanceof DOMElement) {
                continue;
            }

            $data = Json::decode($host->getAttribute('data-control'));
            $data['path'] = $control->path;
            $data['deltaGroup'] = $control->deltaGroup;
            $host->setAttribute('id', $attributes['id']);
            $host->setAttribute('data-control', Json::encode($data, JSON_HEX_AMP | JSON_THROW_ON_ERROR));
            $host->setAttribute('data-scope', Json::encode($renderer->scope()));

            foreach (new Crawler($host)->filter('input[data-nested-modified]') as $input) {
                if ($input instanceof DOMElement && $attributes['name'] !== null) {
                    $input->setAttribute('name', $attributes['name']);
                }
            }
        }

        foreach ($crawler->filter('craft-entry-field-layout-ui[data-field-path]') as $host) {
            if (!$host instanceof DOMElement) {
                continue;
            }

            $host->setAttribute('id', $attributes['id']);
            $path = $control->path;
            $name = $attributes['name'] ?? array_shift($path) . implode('', array_map(fn(string $segment): string => "[{$segment}]", $path));
            foreach (new Crawler($host)->filter('input[data-ui-field-name]') as $input) {
                if ($input instanceof DOMElement) {
                    $input->setAttribute('name', $name);
                }
            }
        }

        return $crawler->filter('body')->html();
    }
}
