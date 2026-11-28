<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Support\Config as ConfigHelper;

/**
 * Settings for how Craft routes requests and builds URLs.
 *
 * @since 6.0.0
 */
class RoutingConfig extends BaseConfig
{
    /**
     * The URI segment Craft should look for when determining if the current request should be routed to a controller action.
     */
    public string $actionTrigger = 'actions';

    /**
     * The URI that users without access to the control panel should be redirected to after activating their account.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getActivateAccountSuccessPath()
     */
    public mixed $activateAccountSuccessPath = '';

    /**
     * Whether auto-generated URLs should have trailing slashes.
     */
    public bool $addTrailingSlashesToUrls = false;

    /**
     * The base URL Craft should use when generating control panel URLs.
     *
     * It will be determined automatically if left blank.
     *
     * ::: tip
     * The base control panel URL should **not** include the control panel trigger word (`cpTrigger`) (e.g. `/admin`).
     * :::
     */
    public ?string $baseCpUrl = null;

    /**
     * The URI segment Craft should look for when determining if the current request should route to the control panel rather than
     * the front-end website.
     *
     * This can be set to `null` if you have a dedicated hostname for the control panel (e.g. `cms.my-project.tld`), or you are running Craft in
     * [Headless Mode](config5:headlessMode). If you do that, you will need to ensure that the control panel is being served from its own web root
     * directory on your server, with an `index.php` file that defines the `CRAFT_CP` PHP constant.
     *
     * ```php
     * define('CRAFT_CP', true);
     * ```
     *
     * Alternatively, you can set the `baseCpUrl` config setting, but then you will run the risk of losing access to portions of your
     * control panel due to URI conflicts with actual folders/files in your main web root.
     *
     * (For example, if you have an `assets/` folder, that would conflict with the `/assets` page in the control panel.)
     */
    public ?string $cpTrigger = 'admin';

    /**
     * The URI Craft should redirect to when user token validation fails. User tokens are used for
     * email verification and password resets. If `null`, `loginPath` will be used by default.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * Note that this only affects front-end site requests.
     *
     * @see getInvalidUserTokenPath()
     */
    public mixed $invalidUserTokenPath = null;

    /**
     * The URI Craft should use for user login on the front end.
     *
     * This is set to `false` by default which disables front-end login.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getLoginPath()
     */
    public mixed $loginPath = false;

    /**
     * The URI Craft should use for user logout on the front end.
     *
     * This can be set to `false` to disable front-end logout.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getLogoutPath()
     */
    public mixed $logoutPath = 'logout';

    /**
     * The query string param name Craft should use for paginated requests.
     *
     * | Example Value | Example URI |
     * | --- | --- |
     * | `page` | `/news?page=5` |
     * | `p` | `/news?p=5` |
     *
     * @see getPageTrigger()
     */
    public string $pageTrigger = 'page';

    /**
     * The path users should be redirected to after logging into the control panel.
     *
     * This setting will also come into effect if a user visits the control panel’s login page (`/admin/login`) or the control panel’s
     * root URL (`/admin`) when they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getPostCpLoginRedirect()
     */
    public mixed $postCpLoginRedirect = 'dashboard';

    /**
     * The path users should be redirected to after logging in from the front-end site.
     *
     * This setting will also come into effect if the user visits the login page (as specified by the `loginPath` config setting) when
     * they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getPostLoginRedirect()
     */
    public mixed $postLoginRedirect = '';

    /**
     * The path that users should be redirected to after logging out from the front-end site.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getPostLogoutRedirect()
     */
    public mixed $postLogoutRedirect = '';

    /**
     * The URI or URL that Craft should use for Set Password forms on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: tip
     * You might also want to set `invalidUserTokenPath` in case a user clicks on an expired password reset link.
     * :::
     *
     * @see getSetPasswordPath()
     */
    public mixed $setPasswordPath = CpAuthPath::SetPassword->value;

    /**
     * The URI to the page where users can request to change their password.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * If this is set, Craft will redirect [.well-known/change-password requests](https://w3c.github.io/webappsec-change-password-url/) to this URI.
     *
     * ::: tip
     * You’ll also need to set `setPasswordPath`, which determines the URI and template path for the Set Password form
     * where the user resets their password after following the link in the Password Reset email.
     * :::
     *
     * @see getSetPasswordRequestPath()
     */
    public mixed $setPasswordRequestPath = null;

    /**
     * The URI Craft should redirect users to after setting their password from the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getSetPasswordSuccessPath()
     */
    public mixed $setPasswordSuccessPath = '';

    /**
     * The query string parameter name that site tokens should be set to.
     */
    public string $siteToken = 'siteToken';

    /**
     * The query string parameter name that Craft tokens should be set to.
     */
    public string $tokenParam = 'token';

    /**
     * Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`, Craft will check the
     * current site’s base URL and the protocol of the current request and if either of them are HTTPS will use `https` in the tokenized URL. If not,
     * will use `http`.
     *
     * If set to `false`, Craft will always use `http`. If set to `true`, then, Craft will always use `https`.
     */
    public string|bool $useSslOnTokenizedUrls = 'auto';

    /**
     * The URI or URL that Craft should use for email verification links on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getVerifyEmailPath()
     */
    public mixed $verifyEmailPath = 'verifyemail';

    /**
     * The URI that users without access to the control panel should be redirected to after verifying a new email address.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * @see getVerifyEmailSuccessPath()
     */
    public mixed $verifyEmailSuccessPath = '';

    public function actionTrigger(string $value): self
    {
        $this->actionTrigger = $value;

        return $this;
    }

    public function activateAccountSuccessPath(mixed $value): self
    {
        $this->activateAccountSuccessPath = $value;

        return $this;
    }

    public function addTrailingSlashesToUrls(bool $value = true): self
    {
        $this->addTrailingSlashesToUrls = $value;

        return $this;
    }

    public function baseCpUrl(?string $value): self
    {
        $this->baseCpUrl = $value;

        return $this;
    }

    public function cpTrigger(?string $value): self
    {
        $this->cpTrigger = $value;

        return $this;
    }

    public function invalidUserTokenPath(mixed $value): self
    {
        $this->invalidUserTokenPath = $value;

        return $this;
    }

    public function loginPath(mixed $value): self
    {
        $this->loginPath = $value;

        return $this;
    }

    public function logoutPath(mixed $value): self
    {
        $this->logoutPath = $value;

        return $this;
    }

    public function pageTrigger(string $value): self
    {
        $this->pageTrigger = $this->normalizePageTrigger($value);

        return $this;
    }

    public function postCpLoginRedirect(mixed $value): self
    {
        $this->postCpLoginRedirect = $value;

        return $this;
    }

    public function postLoginRedirect(mixed $value): self
    {
        $this->postLoginRedirect = $value;

        return $this;
    }

    public function postLogoutRedirect(mixed $value): self
    {
        $this->postLogoutRedirect = $value;

        return $this;
    }

    public function setPasswordPath(mixed $value): self
    {
        $this->setPasswordPath = $value;

        return $this;
    }

    public function setPasswordRequestPath(mixed $value): self
    {
        $this->setPasswordRequestPath = $value;

        return $this;
    }

    public function setPasswordSuccessPath(mixed $value): self
    {
        $this->setPasswordSuccessPath = $value;

        return $this;
    }

    public function siteToken(string $value): self
    {
        $this->siteToken = $value;

        return $this;
    }

    public function tokenParam(string $value): self
    {
        $this->tokenParam = $value;

        return $this;
    }

    public function useSslOnTokenizedUrls(string|bool $value): self
    {
        $this->useSslOnTokenizedUrls = $value;

        return $this;
    }

    public function verifyEmailPath(mixed $value): self
    {
        $this->verifyEmailPath = $value;

        return $this;
    }

    public function verifyEmailSuccessPath(mixed $value): self
    {
        $this->verifyEmailSuccessPath = $value;

        return $this;
    }

    /**
     * Returns the localized Activate Account Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see activateAccountSuccessPath
     */
    public function getActivateAccountSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->activateAccountSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Verify Email Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see verifyEmailPath
     */
    public function getVerifyEmailPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->verifyEmailPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Verify Email Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see verifyEmailSuccessPath
     */
    public function getVerifyEmailSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->verifyEmailSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Invalid User Token Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see invalidUserTokenPath
     */
    public function getInvalidUserTokenPath(?string $siteHandle = null): ?string
    {
        $path = ConfigHelper::localizedValue($this->invalidUserTokenPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Login Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see loginPath
     */
    public function getLoginPath(?string $siteHandle = null): mixed
    {
        $path = ConfigHelper::localizedValue($this->loginPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Logout Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see logoutPath
     */
    public function getLogoutPath(?string $siteHandle = null): mixed
    {
        $path = ConfigHelper::localizedValue($this->logoutPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Post-Login Redirect path for the control panel.
     *
     * @see postCpLoginRedirect
     */
    public function getPostCpLoginRedirect(): string
    {
        return ConfigHelper::localizedValue($this->postCpLoginRedirect);
    }

    /**
     * Returns the localized Post-Login Redirect path.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see postLoginRedirect
     */
    public function getPostLoginRedirect(?string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLoginRedirect, $siteHandle);
    }

    /**
     * Returns the localized Post-Logout Redirect path.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see postLogoutRedirect
     */
    public function getPostLogoutRedirect(?string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLogoutRedirect, $siteHandle);
    }

    /**
     * Returns the localized Set Password Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordPath
     */
    public function getSetPasswordPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Set Password Request Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordRequestPath
     */
    public function getSetPasswordRequestPath(?string $siteHandle = null): ?string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordRequestPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Set Password Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordSuccessPath
     */
    public function getSetPasswordSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the normalized page trigger in query-string form.
     *
     * @see pageTrigger
     */
    public function getPageTrigger(): string
    {
        return '?'.$this->getPageTriggerParam().'=';
    }

    /** Returns the normalized page trigger, without the query string characters. */
    public function getPageTriggerParam(): string
    {
        return $this->normalizePageTrigger($this->pageTrigger);
    }

    private function normalizePageTrigger(string $pageTrigger): string
    {
        $pageTrigger = trim($pageTrigger);

        if ($pageTrigger === '') {
            return 'page';
        }

        $pageTrigger = trim($pageTrigger, '?=');
        $pageTrigger = rtrim($pageTrigger, '/');

        return $pageTrigger !== '' ? $pageTrigger : 'page';
    }
}
