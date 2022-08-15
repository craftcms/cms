<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\FieldLayoutComponent;
use craft\base\Model;
use craft\helpers\Html;

/**
 * FieldLayoutForm model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class FieldLayoutForm extends Model
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
     * Returns the tab menu config.
     *
     * @return array
     */
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
     * @param bool $showFirst Whether the first tab should be shown initially
     * @return string
     */
    public function render(bool $showFirst = true): string
    {
        $html = [];
        $hasMultipleTabs = count($this->tabs) > 1;
        foreach ($this->tabs as $i => $tab) {
            $show = $showFirst && $i === 0;
            $id = $this->_tabId($tab->getId());
            $html[] = Html::tag('div', $tab->getContent(), [
                'id' => $id,
                'class' => array_filter([
                    'flex-fields',
                    !$show ? 'hidden' : null,
                ]),
                'data' => [
                    'id' => $id,
                    'layout-tab' => $tab->getUid() ?? true,
                ],
                'role' => $hasMultipleTabs ? 'tabpanel' : false,
                'aria' => [
                    'labelledBy' => $hasMultipleTabs ? $tab->getTabId() : false,
                ],
            ]);
        }
        return implode("\n", $html);
    }

    /**
     * Returns a tab’s prefixed HTML ID.
     *
     * @param string $tabId
     * @return string
     */
    private function _tabId(string $tabId): string
    {
        return ($this->tabIdPrefix ? "$this->tabIdPrefix-" : '') . $tabId;
    }

    /**
     * Returns lists of visible layout elements’ UUIDs, indexed by their tabs’ UUIDs.
     *
     * @return array
     * @since 4.0.0
     */
    public function getVisibleElements(): array
    {
        $response = [];

        foreach ($this->tabs as $tab) {
            if ($tab->getUid()) {
                $elementUids = [];
                foreach ($tab->elements as [$layoutElement, $isConditional, $elementHtml]) {
                    /** @var FieldLayoutComponent $layoutElement */
                    /** @var bool $isConditional */
                    /** @var string|bool $elementHtml */
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
}
