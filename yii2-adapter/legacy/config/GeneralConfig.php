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
     * @var string[]|null|false The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ::: code
     * ```php Static Config
     * ->allowedGraphqlOrigins(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_GRAPHQL_ORIGINS=false
     * ```
     * :::
     *
     * @group GraphQL
     * @since 3.5.0
     * @deprecated in 4.11.0. [[\craft\filters\Cors]] should be used instead.
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    public array|null|false $allowedGraphqlOrigins = null;

    /**
     * @var bool Whether drafts should be saved automatically as they are edited.
     *
     * Note that drafts *will* be autosaved while Live Preview is open, regardless of this setting.
     *
     * ::: code
     * ```shell Environment Override
     * CRAFT_AUTOSAVE_DRAFTS=false
     * ```
     * :::
     *
     * @group System
     * @since 3.5.6
     * @deprecated in 4.0.0
     */
    public bool $autosaveDrafts = true;

    /**
     * @var bool Whether front-end web requests should support basic HTTP authentication.
     *
     * ::: code
     * ```php Static Config
     * ->enableBasicHttpAuth(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_BASIC_HTTP_AUTH=true
     * ```
     * :::
     *
     * @group Security
     * @since 3.5.0
     * @deprecated in 4.13.0. [[\craft\filters\BasicHttpAuthLogin]] should be used instead.
     */
    public bool $enableBasicHttpAuth = false;

    /**
     * @var string|null The `Permissions-Policy` header that should be sent for site responses.
     *
     * ::: code
     * ```php Static Config
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     * ```shell Environment Override
     * CRAFT_PERMISSIONS_POLICY_HEADER=Permissions-Policy: geolocation=(self)
     * ```
     * :::
     *
     * @group System
     * @since 3.6.14
     * @deprecated in 4.11.0. [[\craft\filters\Headers]] should be used instead.
     */
    public ?string $permissionsPolicyHeader = null;

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

    /**
     * The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ```php
     * ->allowedGraphqlOrigins(false)
     * ```
     *
     * @group GraphQL
     * @param array|null|false $value
     * @return self
     * @see $allowedGraphqlOrigins
     * @since 4.2.0
     * @deprecated in 4.11.0. [[\craft\filters\Cors]] should be used instead.
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    public function allowedGraphqlOrigins(array|null|false $value): self
    {
        $this->allowedGraphqlOrigins = $value;
        return $this;
    }

    /**
     * Whether front-end web requests should support basic HTTP authentication.
     *
     * ```php
     * ->enableBasicHttpAuth(true)
     * ```
     *
     * @group Security
     * @param bool $value
     * @return self
     * @see $enableBasicHttpAuth
     * @since 4.2.0
     */
    public function enableBasicHttpAuth(bool $value = true): self
    {
        $this->enableBasicHttpAuth = $value;
        return $this;
    }

    /**
     * The `Permissions-Policy` header that should be sent for web responses.
     *
     * ```php
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     *
     * @group System
     * @param string|null $value
     * @return self
     * @see $permissionsPolicyHeader
     * @since 4.2.0
     * @deprecated in 4.11.0. [[\craft\filters\Headers]] should be used instead.
     */
    public function permissionsPolicyHeader(?string $value): self
    {
        $this->permissionsPolicyHeader = $value;
        return $this;
    }
}
