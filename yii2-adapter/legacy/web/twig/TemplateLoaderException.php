<?php

namespace craft\web\twig;

use Twig\Error\LoaderError;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Exceptions\TemplateLoaderException} instead.
     */
    class TemplateLoaderException extends LoaderError
    {
    }
}

class_alias(\CraftCms\Cms\Twig\Exceptions\TemplateLoaderException::class, TemplateLoaderException::class);
