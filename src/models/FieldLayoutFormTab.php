<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\Html;

/**
 * FieldLayoutFormTab model class.
 *
 * @property-read string $name The tab’s name
 * @property-read string $id The tab’s HTML ID
 * @property-read string $content The tab’s HTML content
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class FieldLayoutFormTab extends Model
{
    /**
     * @var FieldLayoutTab
     * @since 4.0.0
     */
    public FieldLayoutTab $layoutTab;

    /**
     * @var bool Whether the tab has any validation errors.
     */
    public bool $hasErrors = false;

    /**
     * @var string[]|bool[] The tab’s elements’ HTML content.
     * @since 4.0.0
     */
    public array $elementHtml;

    /**
     * @var bool Whether the tab should be shown.
     * @since 4.0.0
     */
    public bool $visible;

    /**
     * Returns the tab’s name.
     *
     * @return string
     * @since 4.0.0
     */
    public function getName(): string
    {
        return Craft::t('site', $this->layoutTab->name);
    }

    /**
     * Returns the tab’s HTML ID.
     *
     * @return string
     * @since 4.0.0
     */
    public function getId(): string
    {
        return $this->layoutTab->getHtmlId();
    }

    /**
     * Returns the tab’s UUID.
     *
     * @return string
     * @since 4.0.0
     */
    public function getUid(): string
    {
        return $this->layoutTab->uid;
    }

    /**
     * Returns the tab’s HTML content.
     *
     * @return string
     * @since 4.0.0
     */
    public function getContent(): string
    {
        return implode("\n", array_map(function(string $uid, $html) {
            if (is_string($html) && $html) {
                return $html;
            }
            return Html::tag('div', '', [
                'class' => 'hidden',
                'data' => [
                    'layout-element' => $uid,
                ],
            ]);
        }, array_keys($this->elementHtml), $this->elementHtml));
    }
}
