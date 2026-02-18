<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Control panel Twig extension
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.7.8
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Extensions\CpExtension} instead.
     */
    class CpExtension extends AbstractExtension implements GlobalsInterface
    {
    }
}

class_alias(\CraftCms\Cms\Twig\Extensions\CpExtension::class, CpExtension::class);
