<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Error\LoaderError;

class TemplateLoaderException extends LoaderError
{
    public function __construct(
        public string $template,
        string $message
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): Response|bool
    {
        if (app()->hasDebugModeEnabled()) {
            return false;
        }

        return app(ExceptionHandler::class)->render($request, new NotFoundHttpException(previous: $this));
    }
}
