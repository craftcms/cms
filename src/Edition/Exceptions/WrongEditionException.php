<?php

declare(strict_types=1);

namespace CraftCms\Cms\Edition\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WrongEditionException extends Exception
{
    public function render(Request $request): Response|bool
    {
        if (app()->hasDebugModeEnabled()) {
            return false;
        }

        return app(ExceptionHandler::class)->render($request, new NotFoundHttpException(previous: $this));
    }
}
