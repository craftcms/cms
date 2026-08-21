<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class FieldLayoutFormTab extends Component
{
    public FieldLayoutTab $layoutTab;

    public bool $hasErrors = false;

    /** @var FieldLayoutFormElement[] */
    public array $elements;

    public bool $visible;

    public function getName(): string
    {
        return isset($this->layoutTab->name) ? t($this->layoutTab->name, category: 'site') : '';
    }

    public function getId(): string
    {
        return $this->layoutTab->getHtmlId();
    }

    public function getTabId(): string
    {
        return "tab-{$this->getId()}";
    }

    public function getUid(): ?string
    {
        return $this->layoutTab->uid;
    }

    public function getContent(): string
    {
        $components = [];

        foreach ($this->elements as $formElement) {
            if (is_string($formElement->html) && $formElement->html) {
                $components[] = $formElement->html;
            } elseif ($formElement->isConditional) {
                $components[] = Html::tag('div', '', [
                    'class' => 'hidden',
                    'data' => [
                        'layout-element' => $formElement->layoutElement->uid,
                        'layout-element-placeholder' => true,
                    ],
                ]);
            }
        }

        return Html::tag('craft-field-group', implode("\n", $components));
    }
}
