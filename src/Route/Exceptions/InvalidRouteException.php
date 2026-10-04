<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route\Exceptions;

use CraftCms\Cms\Route\Data\Route;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class InvalidRouteException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        public Route $route,
        private readonly array $errors,
    ) {
        parent::__construct(
            collect($errors)->flatten()->first() ?? t('The route is invalid.'),
        );
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
