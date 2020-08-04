<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Component;
use craft\helpers\Gql;
use GraphQL\Error\Error;

/**
 * Class MutationResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class MutationResolver extends Component
{
    /**
     * @var array Data that might be useful during mutation resolution.
     */
    private $_resolutionData;

    /**
     * @var callable[] Value normalizers stored by argument name
     */
    private $_valueNormalizers;

    /**
     * Construct a mutation resolver and store the resolution data as well as normalizers, if any provided.
     *
     * @param array $data Resolver data
     * @param array $valueNormalizers Data normalizers
     */
    public function __construct(array $data = [], array $valueNormalizers = [])
    {
        $this->_resolutionData = $data;
        $this->_valueNormalizers = $valueNormalizers;
    }

    /**
     * Set a piece of data to be used by the resolver when resolving.
     *
     * @param string $key
     * @param $value
     */
    public function setResolutionData(string $key, $value)
    {
        $this->_resolutionData[$key] = $value;
    }

    /**
     * Set a data normalizer for an argument to use for data normalization during resolving.
     *
     * @param string $argument
     * @param callable $normalizer
     */
    public function setValueNormalizer(string $argument, callable $normalizer = null)
    {
        if ($normalizer === null) {
            unset($this->_valueNormalizers[$argument]);
        } else {
            $this->_valueNormalizers[$argument] = $normalizer;
        }
    }

    /**
     * Return stored resolution data.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getResolutionData(string $key)
    {
        return $this->_resolutionData[$key] ?? null;
    }

    /**
     * Normalize a value according to stored normalizers.
     *
     * @param string $argument
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeValue(string $argument, $value)
    {
        if (array_key_exists($argument, $this->_valueNormalizers)) {
            $normalizer = $this->_valueNormalizers[$argument];

            $value = $normalizer($value);
        }

        return $value;
    }

    /**
     * Check if schema can perform the action on a scope and throw an Exception if not.
     *
     * @param string $scope
     * @param string $action
     *
     * @throws \Exception if reasons
     */
    protected function requireSchemaAction(string $scope, string $action)
    {
        if (!Gql::canSchema($scope, $action)) {
            throw new Error('Unable to perform the action.');
        }
    }
}
