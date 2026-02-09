<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Data;

use Craft;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\Support\Html;
use Spatie\LaravelData\Dto;

class FieldLayoutForm extends Dto
{
    /**
     * @var FieldLayoutFormTab[] The form’s tabs.
     */
    public array $tabs = [];

    /**
     * @var string|null The prefix that should be applied to the tab’s HTML IDs.
     */
    public ?string $tabIdPrefix = null;

    /**
     * @var string|null The prefix that should be used for the data-error-key attribute
     */
    public ?string $errorKeyPrefix = null;

    public function getTabMenu(): array
    {
        $menu = [];

        foreach ($this->tabs as $tab) {
            $containerId = $this->_tabId($tab->getId());
            $menu[$containerId] = [
                'tabId' => $tab->getTabId(),
                'label' => $tab->getName(),
                'url' => "#$containerId",
                'class' => $tab->hasErrors ? 'error' : null,
            ];
        }

        return $menu;
    }

    /**
     * Renders the form content.
     *
     * @param  bool  $showFirst  Whether the first tab should be shown initially
     */
    public function render(bool $showFirst = true): string
    {
        $html = [];
        $hasMultipleTabs = count($this->tabs) > 1;
        $view = Craft::$app->getView();

        foreach ($this->tabs as $i => $tab) {
            $show = $showFirst && $i === 0;
            $id = $this->_tabId($tab->getId());
            $html[] = Html::tag('div', $tab->getContent(), [
                'id' => $id,
                'class' => array_filter([
                    'flex-fields',
                    ! $show ? 'hidden' : null,
                ]),
                'data' => [
                    'id' => $id,
                    'layout-tab' => $tab->getUid() ?? true,
                ],
                'role' => $hasMultipleTabs ? 'tabpanel' : false,
                'aria' => [
                    'labelledBy' => $hasMultipleTabs ? $view->namespaceInputId($tab->getTabId()) : false,
                ],
            ]);
        }

        return implode("\n", $html);
    }

    /**
     * Returns a tab’s prefixed HTML ID.
     */
    private function _tabId(string $tabId): string
    {
        return ($this->tabIdPrefix ? "$this->tabIdPrefix-" : '').$tabId;
    }

    /**
     * Returns lists of visible layout elements’ UUIDs, indexed by their tabs’ UUIDs.
     */
    public function getVisibleElements(): array
    {
        $response = [];

        foreach ($this->tabs as $tab) {
            if ($tab->getUid()) {
                $elementUids = [];
                foreach ($tab->elements as [$layoutElement, $isConditional, $elementHtml, $isStatic]) {
                    /** @var FieldLayoutComponent $layoutElement */
                    /** @var bool $isConditional */
                    /** @var string|bool $elementHtml */
                    /** @var bool $isStatic */
                    if ($isConditional && $elementHtml) {
                        $elementUids[] = $layoutElement->uid;
                    }
                }
                if ($elementUids) {
                    $response[$tab->getUid()] = $elementUids;
                }
            }
        }

        return $response;
    }

    /**
     * Returns lists of visible but static layout elements’ UUIDs, indexed by their tabs’ UUIDs.
     */
    public function getStaticElements(): array
    {
        $response = [];

        foreach ($this->tabs as $tab) {
            if ($tab->getUid()) {
                $elementUids = [];
                foreach ($tab->elements as [$layoutElement, $isConditional, $elementHtml, $isStatic]) {
                    /** @var FieldLayoutComponent $layoutElement */
                    /** @var bool $isConditional */
                    /** @var string|bool $elementHtml */
                    /** @var bool $isStatic */
                    if ($isConditional && $elementHtml && $isStatic) {
                        $elementUids[] = $layoutElement->uid;
                    }
                }
                if ($elementUids) {
                    $response[$tab->getUid()] = $elementUids;
                }
            }
        }

        return $response;
    }
}
