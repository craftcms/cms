<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Exceptions;

use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class EarlyPublicationFailed extends RuntimeException
{
    public function __construct(public mixed $response)
    {
        parent::__construct(t('The draft could not be published.'));
    }
}
