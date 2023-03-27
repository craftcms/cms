<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use craft\web\twig\nodevisitors\FallbackVariableSwapper;
use Twig\Extension\AbstractExtension;

/**
 * Site Twig extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class SiteExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getNodeVisitors(): array
    {
        return [
            new FallbackVariableSwapper(),
        ];
    }
}
