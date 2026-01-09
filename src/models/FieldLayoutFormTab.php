<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\FieldLayoutComponent;
use craft\base\FieldLayoutElement;
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
     * @var array{0:FieldLayoutElement,1:bool,2:string|false,3:bool}[] The tab’s elements, whether they’re conditional,
     * their HTML form HTML, and whether they were rendered statically.
     * @since 4.0.0
     */
    public array $elements;

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
        if (!isset($this->layoutTab->name)) {
            return '';
        }

        return Craft::t('site', $this->layoutTab->name);
    }

    /**
     * Returns the tab anchor’s HTML ID.
     *
     * @return string
     * @since 4.0.0
     */
    public function getTabId(): string
    {
        return sprintf('tab-%s', $this->getId());
    }

    /**
     * Returns the content container’s HTML ID.
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
     * @return string|null
     * @since 4.0.0
     */
    public function getUid(): ?string
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
        $components = [];

        foreach ($this->elements as [$layoutElement, $isConditional, $elementHtml, $isStatic]) {
            /** @var FieldLayoutComponent $layoutElement */
            /** @var bool $isConditional */
            /** @var string|bool $elementHtml */
            /** @var bool $isStatic */
            if (is_string($elementHtml) && $elementHtml) {
                $components[] = $elementHtml;
            } elseif ($isConditional) {
                $components[] = Html::tag('div', '', [
                    'class' => 'hidden',
                    'data' => [
                        'layout-element' => $layoutElement->uid,
                        'layout-element-placeholder' => true,
                    ],
                ]);
            }
        }

        return implode("\n", $components);
    }
}
