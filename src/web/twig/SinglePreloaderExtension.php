<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use craft\web\twig\nodevisitors\SinglePreloader;
use Twig\Extension\AbstractExtension;

/**
 * Single preloader Twig extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class SinglePreloaderExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getNodeVisitors(): array
    {
        return [
            new SinglePreloader(),
        ];
    }
}
