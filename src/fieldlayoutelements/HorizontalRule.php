<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\helpers\Html;

/**
 * HorizontalRule represents an `<hr>` UI element can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class HorizontalRule extends FieldLayoutElement
{
    /**
     * @inheritdoc
     */
    protected function conditional(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function selectorHtml(): string
    {
        $label = Craft::t('app', 'Horizontal Rule');
        return <<<HTML
<div class="fld-hr">
  <div class="smalltext light">$label</div>
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Html::tag('hr');
    }
}
