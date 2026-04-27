<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Gql\GqlHelper;
use Exception;
use GraphQL\Error\Error;

abstract class MutationResolver extends Component
{
    /**
     * @param  array  $_resolutionData  Resolver data
     * @param  array  $_valueNormalizers  Data normalizers
     */
    public function __construct(private array $_resolutionData = [], private array $_valueNormalizers = [], array $config = [])
    {
        parent::__construct($config);
    }

    public function setResolutionData(string $key, mixed $value): void
    {
        $this->_resolutionData[$key] = $value;
    }

    public function setValueNormalizer(string $argument, ?callable $normalizer = null): void
    {
        if ($normalizer === null) {
            unset($this->_valueNormalizers[$argument]);
        } else {
            $this->_valueNormalizers[$argument] = $normalizer;
        }
    }

    public function getResolutionData(string $key): mixed
    {
        return $this->_resolutionData[$key] ?? null;
    }

    protected function normalizeValue(string $argument, mixed $value): mixed
    {
        if (array_key_exists($argument, $this->_valueNormalizers)) {
            $normalizer = $this->_valueNormalizers[$argument];

            $value = $normalizer($value);
        }

        return $value;
    }

    /**
     * @throws Exception if reasons
     */
    protected function requireSchemaAction(string $scope, string $action): void
    {
        if (! GqlHelper::canSchema($scope, $action)) {
            throw new Error('Unable to perform the action.');
        }
    }
}
