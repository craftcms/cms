<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Ui\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Yii2Adapter\Ui\Controls\LegacyHtmlControl;
use InvalidArgumentException;

class LegacyHtmlField implements Node
{
    public function __construct(private readonly LegacyHtmlControl $control)
    {
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        if ($node->control === null) {
            throw new InvalidArgumentException('Legacy HTML Field Nodes require a Control payload.');
        }

        $errors = $renderer->errorsFor($payload->errors, $node->control->path);
        $html = $renderer->renderControl(
            $node->control,
            $payload->values,
            $renderer->id($node->control->path),
            $errors !== [],
            false,
        );

        if ($errors !== []) {
            $html .= Html::tag('ul', implode('', array_map(
                fn(string $error): string => Html::tag('li', Html::encode($error)),
                $errors,
            )), ['class' => 'error-list', 'role' => 'alert']);
        }

        return Html::tag('div', $html, [
            'id' => $renderer->id($node->control->path),
            'aria' => ['invalid' => $errors === [] ? null : 'true'],
            'data-ui-control-path' => Json::encode($node->control->path),
        ]);
    }

    public function component(): string
    {
        return 'craft-legacy:html-field';
    }

    public function uid(): ?string
    {
        return null;
    }

    public function props(): array
    {
        return [];
    }

    public function getControl(): LegacyHtmlControl
    {
        return $this->control;
    }

    public function children(): array
    {
        return [];
    }
}
