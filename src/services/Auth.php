<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\MemoizableArray;
use craft\errors\AuthProviderNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\auth\ProviderInterface;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use Throwable;

class Auth extends Component
{
    const PROJECT_CONFIG_PATH = 'auth';

    /**
     * @var MemoizableArray<ProviderInterface>|null
     * @see _providers()
     */
    private ?MemoizableArray $_providers = null;

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_providers']);
        return $vars;
    }

    /**
     * @param ProviderInterface[] $providers list of Authorization Providers
     */
    protected function setProviders(array $providers)
    {
        $this->_providers = $this->initProviders($providers);
    }

    /**
     * Returns a memoizable array of all providers.
     *
     * @return MemoizableArray<ProviderInterface>
     */
    private function _providers(): MemoizableArray
    {
        if (!isset($this->_providers)) {
            $this->_providers = $this->initProviders();
        }

        return $this->_providers;
    }

    /**
     * @param array $baseProviders
     * @return MemoizableArray<ProviderInterface>
     */
    private function initProviders(array $baseProviders = []): MemoizableArray
    {
        $configs = ArrayHelper::merge(
            $baseProviders,
            Craft::$app->getProjectConfig()->get(self::PROJECT_CONFIG_PATH) ?? []
        );

        $providers = array_map(function(string $handle, array $config) {
            $config['handle'] = $handle;
            $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);
            return $this->createAuthProvider($config);
        }, array_keys($configs), $configs);

        return new MemoizableArray($providers);
    }

    /**
     * Creates an auth provider from a given config.
     *
     * @template T as ProviderInterface
     * @param string|array $config The auth provider’s class name, or its config, with a `type` value and optionally a `settings` value
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The filesystem
     */
    public function createAuthProvider(mixed $config): ProviderInterface
    {
        try {
            return ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (MissingComponentException|InvalidConfigException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'] ?? 'ProviderInterface';
            /** @var array $config */
            /** @phpstan-var array{errorMessage:string,expectedType:string,type:string} $config */
            unset($config['type']);
            return new Exception("Invalid auth provider");
        }
    }

    /**
     * Removes a filesystem.
     *
     * @param ProviderInterface $provider The auth provider to remove
     * @return bool
     * @throws Throwable
     */
    public function removeAuthProvider(ProviderInterface $provider): bool
    {
        if (!$provider->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(sprintf('%s.%s', static::PROJECT_CONFIG_PATH, $provider->handle), "Remove the “{$provider->handle}” auth provider");

        // Clear caches
        $this->_providers = null;

        return true;
    }

    /**
     * Return a list of all auth providers
     *
     * @return ProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->_providers()->all();
    }

    /**
     * Returns an auth provider by its handle.
     *
     * @param string $handle
     * @return ProviderInterface|null
     */
    public function findProviderByHandle(string $handle): ?ProviderInterface
    {
        return $this->_providers()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns an auth provider by its handle.
     *
     * @param string $handle
     * @return ProviderInterface
     * @throws AuthProviderNotFoundException
     */
    public function getProviderByHandle(string $handle): ProviderInterface
    {
        $provider = $this->findProviderByHandle($handle);
        if (!$provider) {
            throw new AuthProviderNotFoundException();
        }

        return $provider;
    }
}
