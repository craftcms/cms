<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

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
    public $tabs = [];

    /**
     * @var string|null The prefix that should be applied to the tab’s HTML IDs.
     */
    public $tabIdPrefix;

    /**
     * Returns the tab menu config.
     *
     * @return array
     */
    public function getTabMenu(): array
    {
        $menu = [];
        foreach ($this->tabs as $tab) {
            $tabId = $this->_tabId($tab->id);
            $menu[$tabId] = [
                'label' => $tab->name,
                'url' => "#$tabId",
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
        foreach ($this->tabs as $i => $tab) {
            $show = $showFirst && $i === 0;
            $html[] = Html::tag('div', $tab->content, [
                'id' => $this->_tabId($tab->id),
                'class' => array_filter([
                    'flex-fields',
                    !$show ? 'hidden' : null,
                ]),
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
}
