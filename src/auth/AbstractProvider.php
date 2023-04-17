<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use Craft;
use craft\base\SavableComponent;
use craft\elements\User;
use craft\errors\AuthFailedException;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\User as UserHelper;
use yii\web\Response;

abstract class AbstractProvider extends SavableComponent implements ProviderInterface
{
    use AuthProviderTrait;

    public function getSiteLoginHtml(): string|null
    {
        return null;
    }

    public function getCpLoginHtml(): string|null
    {
        return null;
    }

    protected function loginUser(User $user, bool $rememberMe = false): bool
    {
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            return true;
        }

        // Get the session duration
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        $user->authError = UserHelper::getAuthStatus($user);

        if (!empty($user->authError)) {
            throw new AuthFailedException($this, $user, $user->authError);
        }

        // Try logging them in
        if (!$userSession->login($user, $duration)) {
            throw new AuthFailedException($this, $user, Craft::t('auth', "Unable to login"));
        }

        return true;
    }

    /**
     * Render login HTML for a provider
     *
     * @param string $template
     * @param array|null $variables
     * @return string
     * @throws \craft\web\twig\TemplateLoaderException
     */
    protected function renderLoginHtml(string $template, ?array $variables = []): string
    {
        return Cp::renderTemplate($template, ArrayHelper::merge(
            [
                'loginAction' => 'auth/login',
                'provider' => $this
            ],
            $variables,
        ));
    }
}
