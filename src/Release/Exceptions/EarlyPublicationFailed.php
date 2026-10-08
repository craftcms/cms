<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class EarlyPublicationFailed extends RuntimeException
{
    public function __construct(public Response $response)
    {
        parent::__construct(t('The draft could not be published.'));
    }
}
