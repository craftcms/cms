<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\fs\bridge\LegacyFsFlysystemAdapter;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Yii2Adapter\ModelWrapper;
use CraftCms\Yii2Adapter\Validation\LegacyYiiRules;
use yii\base\InvalidConfigException;

/**
 * Field is the base class for classes representing filesystems in terms of objects.
 *
 * @property-read null|string $rootUrl
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0
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
        return LegacyYiiRules::mergeAttributeRules(
            rules: parent::getRules(),
            target: $this,
            yiiRules: $this->defineRules(),
            validatorTarget: fn() => new ModelWrapper($this),
            allowMethodValidators: true,
        );
    }

    /**
     * @return array<int, array|string>
     */
    protected function defineRules(): array
    {
        return [];
    }
}
