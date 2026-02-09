<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use CraftCms\Cms\Support\Html as HtmlHelper;
use yii\base\NotSupportedException;

/**
 * Html represents a field layout component that displays arbitrary HTML.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 */
class Html extends FieldLayoutElement
{
    /**
     * Constructor
     */
    public function __construct(private readonly string $html, array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotSupportedException
     */
    public function selectorHtml(): string
    {
        throw new NotSupportedException(sprintf('%s should not be included in user-modifyable field layouts.', self::class));
    }

    /**
     * {@inheritdoc}
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return HtmlHelper::tag('div', $this->html);
    }
}
