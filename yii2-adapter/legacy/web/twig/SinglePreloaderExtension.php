<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Twig\Extension\AbstractExtension;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Single preloader Twig extension
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Extensions\SinglePreloaderExtension} instead.
     */
    class SinglePreloaderExtension extends AbstractExtension
    {
    }
}

class_alias(\CraftCms\Cms\Twig\Extensions\SinglePreloaderExtension::class, SinglePreloaderExtension::class);
