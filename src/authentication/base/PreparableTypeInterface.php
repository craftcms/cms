<?php

declare(strict_types=1);

namespace craft\authentication\base;

/**
 * PreparableTypeInterface must be implemented by all steps in authentication chains that require preparation, before
 * displaying the HTML input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface PreparableTypeInterface
{
    /**
     * Perform any actions that are required before authentication can take place.
     *
     * @return void
     */
    public function prepareForAuthentication(): void;
}
