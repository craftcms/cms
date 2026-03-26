<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Exceptions;

use CraftCms\Cms\Twig\PageLifecycle;

/**
 * Thrown by the `{% exit %}` Twig tag (without a status code) to gracefully
 * stop template rendering and return whatever has been rendered so far as
 * a normal 200 response.
 *
 * Caught by {@see PageLifecycle} to capture buffered output.
 */
class TemplateExitException extends \RuntimeException {}
