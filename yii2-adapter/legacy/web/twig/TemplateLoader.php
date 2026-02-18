<?php

namespace craft\web\twig;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Loads Craft templates into Twig.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\TemplateLoader} instead.
     */
    class TemplateLoader
    {
    }
}

class_alias(\CraftCms\Cms\Twig\TemplateLoader::class, TemplateLoader::class);
