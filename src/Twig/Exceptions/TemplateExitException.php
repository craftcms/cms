<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Exceptions;

/**
 * Thrown by the `{% exit %}` Twig tag (without a status code) to gracefully
 * stop template rendering and return whatever has been rendered so far as
 * a normal 200 response.
 *
 * Caught by {@see \CraftCms\Cms\Twig\PageLifecycle} to capture buffered output.
 */
final class TemplateExitException extends \RuntimeException {}
