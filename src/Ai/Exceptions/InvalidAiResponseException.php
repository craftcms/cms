<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Exceptions;

use RuntimeException;

/**
 * Thrown when a model’s response can’t be used, such as a generated value that doesn’t match its field.
 *
 * @since 6.0.0
 */
class InvalidAiResponseException extends RuntimeException {}
