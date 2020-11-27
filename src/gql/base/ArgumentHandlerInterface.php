<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;


/**
 * Class ArgumentHandlerInterface
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
interface ArgumentHandlerInterface
{
    /**
     * Handle an argument collection
     *
     * @param array $argumentList argument list to be used for the query
     * @return mixed
     */
    public function handleArgumentCollection(array $argumentList = []): array;
}
