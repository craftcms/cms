<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Component;
use craft\helpers\Gql;
use Exception;
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
    private array $_resolutionData;

    /**
     * @var callable[] Value normalizers stored by argument name
     */
    private array $_valueNormalizers = [];

    /**
     * Construct a mutation resolver and store the resolution data as well as normalizers, if any provided.
     *
     * @param array $data Resolver data
     * @param array $valueNormalizers Data normalizers
     * @param array $config
     */
    public function __construct(array $data = [], array $valueNormalizers = [], array $config = [])
    {
        $this->_resolutionData = $data;
        $this->_valueNormalizers = $valueNormalizers;
        parent::__construct($config);
    }

    /**
     * Set a piece of data to be used by the resolver when resolving.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setResolutionData(string $key, mixed $value): void
    {
        $this->_resolutionData[$key] = $value;
    }

    /**
     * Set a data normalizer for an argument to use for data normalization during resolving.
     *
     * @param string $argument
     * @param callable|null $normalizer
     */
    public function setValueNormalizer(string $argument, ?callable $normalizer = null): void
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
     * @return mixed
     */
    public function getResolutionData(string $key): mixed
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
    protected function normalizeValue(string $argument, mixed $value): mixed
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
     * @throws Exception if reasons
     */
    protected function requireSchemaAction(string $scope, string $action): void
    {
        if (!Gql::canSchema($scope, $action)) {
            throw new Error('Unable to perform the action.');
        }
    }
}
