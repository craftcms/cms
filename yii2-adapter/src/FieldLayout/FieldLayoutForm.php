<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;

class FieldLayoutForm extends Component
{
    public ?string $html = null;

    /** @var array<string, array{tabId: string, label: string, url: string, class: string|null}> */
    public array $tabMenu = [];

    /** @var FieldLayoutFormTab[] */
    public array $tabs = [];

    public ?string $tabIdPrefix = null;

    public ?string $errorKeyPrefix = null;

    /** @param string|list<string> $namespace */
    public static function fromLayout(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        bool $static = false,
        string|array $namespace = [],
    ): self {
        $payload = app(FieldLayoutCompiler::class)->compile(
            $layout,
            $element,
            new FormContext(
                namespace: $namespace,
                mode: $static ? ControlMode::ReadOnly : ControlMode::Editable,
            ),
        );
        $renderer = app(FormHtmlRenderer::class);

        return new self([
            'html' => $renderer->render($payload),
            'tabMenu' => $renderer->tabMenu($payload),
        ]);
    }

    /** @return array<string, array{tabId: string, label: string, url: string, class: string|null}> */
    public function getTabMenu(): array
    {
        if ($this->html !== null) {
            return $this->tabMenu;
        }

        $menu = [];

        foreach ($this->tabs as $tab) {
            $containerId = $this->tabId($tab->getId());
            $menu[$containerId] = [
                'tabId' => $tab->getTabId(),
                'label' => $tab->getName(),
                'url' => "#{$containerId}",
                'class' => $tab->hasErrors ? 'error' : null,
            ];
        }

        return $menu;
    }

    public function render(bool $showFirst = true): string
    {
        if ($this->html !== null) {
            return $this->html;
        }

        $html = [];
        $hasMultipleTabs = count($this->tabs) > 1;

        foreach ($this->tabs as $i => $tab) {
            $show = $showFirst && $i === 0;
            $id = $this->tabId($tab->getId());
            $html[] = Html::tag('div', $tab->getContent(), [
                'id' => $id,
                'class' => array_filter(['flex-fields', !$show ? 'hidden' : null]),
                'data' => ['id' => $id, 'layout-tab' => $tab->getUid() ?? true],
                'role' => $hasMultipleTabs ? 'tabpanel' : false,
                'aria' => [
                    'labelledBy' => $hasMultipleTabs ? InputNamespace::namespaceId($tab->getTabId()) : false,
                ],
            ]);
        }

        return implode("\n", $html);
    }

    private function tabId(string $tabId): string
    {
        return ($this->tabIdPrefix ? "{$this->tabIdPrefix}-" : '') . $tabId;
    }

    /** @return array<string, list<string>> */
    public function getVisibleElements(): array
    {
        return $this->elements(false);
    }

    /** @return array<string, list<string>> */
    public function getStaticElements(): array
    {
        return $this->elements(true);
    }

    /** @return array<string, list<string>> */
    private function elements(bool $staticOnly): array
    {
        $response = [];

        foreach ($this->tabs as $tab) {
            if (!$tab->getUid()) {
                continue;
            }

            foreach ($tab->elements as $formElement) {
                if (
                    $formElement->isConditional
                    && $formElement->html
                    && (!$staticOnly || $formElement->isStatic)
                ) {
                    $response[$tab->getUid()][] = $formElement->layoutElement->uid;
                }
            }
        }

        return $response;
    }
}
