<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Exceptions;

use Twig\Error\LoaderError;

class TemplateLoaderException extends LoaderError
{
    public function __construct(
        public string $template,
        string $message
    ) {
        parent::__construct($message);
    }
}
