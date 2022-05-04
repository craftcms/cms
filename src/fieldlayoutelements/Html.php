<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\helpers\Html as HtmlHelper;
use yii\base\NotSupportedException;

/**
 * Html represents a field layout component that displays arbitrary HTML.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Html extends FieldLayoutElement
{
    private string $html;

    /**
     * Constructor
     */
    public function __construct(string $html, array $config = [])
    {
        $this->html = $html;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function selectorHtml(): string
    {
        throw new NotSupportedException(sprintf('%s should not be included in user-modifyable field layouts.', __CLASS__));
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return HtmlHelper::tag('div', $this->html);
    }
}
