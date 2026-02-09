<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Component\Concerns\ConfigConstructor;
use CraftCms\Cms\Support\Html;
use Spatie\LaravelData\Dto;

use function CraftCms\Cms\t;

class FieldLayoutFormTab extends Dto
{
    use ConfigConstructor;

    public FieldLayoutTab $layoutTab;

    /**
     * @var bool Whether the tab has any validation errors.
     */
    public bool $hasErrors = false;

    /**
     * @var FieldLayoutFormElement[] The tab’s elements, whether they’re conditional, their HTML form HTML, and whether they were rendered statically.
     */
    public array $elements;

    /**
     * @var bool Whether the tab should be shown.
     */
    public bool $visible;

    public string $name {
        get => $this->getName();
    }

    public string $id {
        get => $this->getId();
    }

    public string $content {
        get => $this->getContent();
    }

    public function getName(): string
    {
        if (! isset($this->layoutTab->name)) {
            return '';
        }

        return t($this->layoutTab->name, category: 'site');
    }

    public function getId(): string
    {
        return $this->layoutTab->getHtmlId();
    }

    /**
     * Returns the tab anchor’s HTML ID.
     */
    public function getTabId(): string
    {
        return sprintf('tab-%s', $this->id);
    }

    public function getUid(): ?string
    {
        return $this->layoutTab->uid;
    }

    /**
     * Returns the tab’s HTML content.
     */
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

        return implode("\n", $components);
    }
}
