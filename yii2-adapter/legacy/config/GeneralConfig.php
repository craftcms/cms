<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use craft\services\Config;
use CraftCms\Cms\Support\Facades\Deprecator;
use Deprecated;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

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
     * @var string|array|null|false Configures Craft to send all system emails to either a single email address or an array of email addresses
     *                              for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['me@domain.tld' => 'Name']`.
     *
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     */
    public string|array|null|false $testToEmailAddress = null;

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
            ->invalidLoginWindowDuration($this->invalidLoginWindowDuration)
            ->previewTokenDuration($this->previewTokenDuration ?? $this->defaultTokenDuration)
            ->purgeStaleUserSessionDuration($this->purgeStaleUserSessionDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->rememberedUserSessionDuration($this->rememberedUserSessionDuration)
            ->softDeleteDuration($this->softDeleteDuration)
            ->verificationCodeDuration($this->verificationCodeDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->maxUploadFileSize($this->maxUploadFileSize)
            ->disabledPlugins($this->disabledPlugins)
        ;
    }

    /**
     * Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     *
     * @see $testToEmailAddress
     */
    #[Deprecated(message: 'in 6.0.0. Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` instead.')]
    public function testToEmailAddress(string|array|null|false $value): self
    {
        app()->booted(fn() => Deprecator::log(
            'generalConfig.testToEmailAddress',
            '`craft\\config\\GeneralConfig::$testToEmailAddress` and `craft\\config\\GeneralConfig::testToEmailAddress()` are deprecated. Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` in your application bootstrap or service provider instead.',
        ));

        $this->testToEmailAddress = $value;

        return $this;
    }

    /**
     * Returns the normalized test email addresses.
     */
    public function getTestToEmailAddress(): array
    {
        $to = [];
        if ($this->testToEmailAddress) {
            foreach ((array)$this->testToEmailAddress as $key => $value) {
                if (is_numeric($key)) {
                    $to[$value] = t('Test Recipient');
                } else {
                    $to[$key] = $value;
                }
            }
        }

        return $to;
    }
}
