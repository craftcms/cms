<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\errors\GqlException;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MutationResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class MutationResolver
{
    private $_resolutionData = [];

    /**
     * Construct a mutation resolver and store the resolution data.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->_resolutionData = $data;
    }

    /**
     * Return stored resolution data.
     *
     * @param string $key
     * @return mixed
     * @throws GqlException
     */
    protected function _getData(string $key)
    {
        if (!isset($this->_resolutionData[$key])) {
            throw new GqlException('Stored resolution data by key “' . $key . '” not found!');
        }

        return $this->_resolutionData[$key];
    }

    /**
     * Resolve a mutation field by source, arguments, context and resolution information.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    abstract public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);
}
