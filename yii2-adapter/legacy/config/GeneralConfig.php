<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use craft\services\Config;
use yii\base\InvalidConfigException;

/**
 * General config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Config\GeneralConfig]] should be used instead.
 */
class GeneralConfig extends \CraftCms\Cms\Config\GeneralConfig
{
    /**
     * @inheritdoc
     */
    protected ?string $filename = Config::CATEGORY_GENERAL;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        // (Re-)normalize everything.
        // Even if they were already set via the fluent methods, \Craft may not have been autoloaded yet,
        // so some values would still be in need of normalization, e.g. defaultCpLanguage/extraAppLocales.
        $this
            // file extensions
            ->allowedFileExtensions($this->allowedFileExtensions)
            ->extraAllowedFileExtensions($this->extraAllowedFileExtensions)
            // durations
            ->cacheDuration($this->cacheDuration)
            ->cooldownDuration($this->cooldownDuration)
            ->defaultTokenDuration($this->defaultTokenDuration)
            ->elevatedSessionDuration($this->elevatedSessionDuration)
            ->invalidLoginWindowDuration($this->invalidLoginWindowDuration)
            ->previewTokenDuration($this->previewTokenDuration ?? $this->defaultTokenDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->rememberedUserSessionDuration($this->rememberedUserSessionDuration)
            ->softDeleteDuration($this->softDeleteDuration)
            ->userSessionDuration($this->userSessionDuration)
            ->verificationCodeDuration($this->verificationCodeDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->maxUploadFileSize($this->maxUploadFileSize)
            ->disabledPlugins($this->disabledPlugins)
        ;
    }
}
