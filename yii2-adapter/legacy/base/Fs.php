<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Closure;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\ModelWrapper;
use ReflectionFunction;
use ReflectionMethod;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Field is the base class for classes representing filesystems in terms of objects.
 *
 * @property-read null|string $rootUrl
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class Fs extends Filesystem implements BaseFsInterface, FsInterface
{
    public function getDiskConfig(): array
    {
        if (!is_string($this->handle) || $this->handle === '') {
            throw new InvalidConfigException('Filesystem handle is missing.');
        }

        $config = [
            'driver' => LegacyFsFlysystemAdapter::DISK_DRIVER,
            'fsHandle' => $this->handle,
        ];

        $rootUrl = $this->getRootUrl();
        if (is_string($rootUrl) && $rootUrl !== '') {
            $config['url'] = rtrim($rootUrl, '/');
        }

        return $config;
    }

    public function getRules(): array
    {
        $yiiRules = $this->defineRules();

        $rules = parent::getRules();
        $legacyAttributes = [];

        foreach ($yiiRules as $rule) {
            if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                continue;
            }

            foreach ((array)$rule[0] as $attribute) {
                if (is_string($attribute) && $attribute !== '') {
                    $legacyAttributes[$attribute] = true;
                }
            }
        }

        foreach (array_keys($legacyAttributes) as $legacyAttribute) {
            $rules[$legacyAttribute] ??= [];
            $rules[$legacyAttribute] = Arr::wrap($rules[$legacyAttribute]);

            array_unshift($rules[$legacyAttribute], function($attribute, $value, $fail) use ($yiiRules) {
                foreach ($yiiRules as $rule) {
                    if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                        continue;
                    }

                    $attributes = (array)$rule[0];
                    $type = $rule[1];
                    $options = array_slice($rule, 2, null, true);

                    if (!in_array($attribute, $attributes, true)) {
                        continue;
                    }

                    if (is_string($type) && method_exists($this, $type)) {
                        $method = $type;
                        $filesystem = $this;
                        $type = function(string $attribute, ?array $params, Validator $validator, mixed $current) use ($method, $filesystem): void {
                            $parameterCount = (new ReflectionMethod($filesystem, $method))->getNumberOfParameters();

                            match (true) {
                                $parameterCount === 0 => $filesystem->$method(),
                                $parameterCount === 1 => $filesystem->$method($attribute),
                                $parameterCount === 2 => $filesystem->$method($attribute, $params),
                                $parameterCount === 3 => $filesystem->$method($attribute, $params, $validator),
                                default => $filesystem->$method($attribute, $params, $validator, $current),
                            };
                        };
                    }

                    if (isset($options['when']) && is_callable($options['when'])) {
                        $options['when'] = $this->normalizeWhenCallback($options['when']);
                    }

                    $wrappedModel = new ModelWrapper($this);
                    $validator = Validator::createValidator($type, $wrappedModel, $attributes, $options);
                    $validator->validateAttribute($wrappedModel, $attribute);

                    foreach ($wrappedModel->getErrors($attribute) as $error) {
                        $fail((string)$error);
                    }
                }
            });
        }

        return $rules;
    }

    /**
     * @return array<int, array|string>
     */
    protected function defineRules(): array
    {
        return [];
    }

    private function normalizeWhenCallback(callable $callback): Closure
    {
        return function($model, string $attribute) use ($callback): bool {
            $callback = Closure::fromCallable($callback);
            $parameterCount = (new ReflectionFunction($callback))->getNumberOfParameters();

            return match (true) {
                $parameterCount === 0 => (bool)$callback(),
                $parameterCount === 1 => (bool)$callback($this),
                default => (bool)$callback($this, $attribute),
            };
        };
    }
}
