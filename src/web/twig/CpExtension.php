<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\helpers\Cp;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Control panel Twig extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.8
 */
class CpExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        return [
            'CraftEdition' => Craft::$app->getEdition(),
            'CraftSolo' => Craft::Solo,
            'CraftPro' => Craft::Pro,
            'requestedSite' => Cp::requestedSite(),
        ];
    }
}
