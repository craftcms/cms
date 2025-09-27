<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Globals Twig extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.8
 */
class GlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        $globals = [];
        foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
            $globals[$globalSet->handle] = $globalSet;
        }
        return $globals;
    }
}
