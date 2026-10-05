<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Json;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** Renders native nested controls with relative input names for legacy field callers. */
class NestedElementFieldHtml
{
    public function __construct(
        private readonly FormResolver $resolver,
        private readonly FormHtmlRenderer $renderer,
    ) {
    }

    public function render(Control $control, string $inputId, string $inputName, ControlMode $mode): string
    {
        $payload = $this->resolver->resolve(
            Form::make([Field::make()->control($control)]),
            new FormContext(mode: $mode),
        );
        $control = $payload->nodes[0]->control;

        return $control->type::renderHtml($control, null, [
            'id' => $inputId,
            'name' => $control->mode === ControlMode::Editable ? $inputName : null,
        ], $this->renderer);
    }

    /**
     * Legacy fragments are captured before nested Form scopes have been resolved.
     *
     * @param array<string, mixed> $attributes
     */
    public function rebase(string $html, ControlPayload $control, array $attributes, FormHtmlRenderer $renderer): string
    {
        if (!str_contains($html, '<craft-nested-elements-control')) {
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

        return $crawler->filter('body')->html();
    }
}
