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
use Twig\TwigFunction;

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

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('disclosureMenu', [Cp::class, 'disclosureMenu'], ['is_safe' => ['html']]),
            new TwigFunction('elementCard', [Cp::class, 'elementCardHtml'], ['is_safe' => ['html']]),
            new TwigFunction('elementChip', [Cp::class, 'elementChipHtml'], ['is_safe' => ['html']]),
            new TwigFunction('elementIndex', [Cp::class, 'elementIndexHtml'], ['is_safe' => ['html']]),
            new TwigFunction('statusIndicator', [Cp::class, 'statusIndicatorHtml'], ['is_safe' => ['html']]),
            new TwigFunction('siteMenuItems', [Cp::class, 'siteMenuItems']),
        ];
    }
}
