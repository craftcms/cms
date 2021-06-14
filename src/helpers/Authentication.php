<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\models\authentication\State;
use PragmaRX\Google2FAQRCode\Google2FA;
use yii\base\InvalidConfigException;

/**
 * Class Authentication
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Authentication
{

    /**
     * Create an authentication type based on a config.
     *
     * @param array $typeConfig
     * @param State $state
     * @return TypeInterface
     * @throws InvalidConfigException
     */
    public static function createStepFromConfig(array $typeConfig, State $state): TypeInterface
    {
        $class = $typeConfig['type'];

        if (!is_subclass_of($class, TypeInterface::class)) {
            throw new InvalidConfigException('Impossible to create authentication type.');
        }

        $settings = array_merge($typeConfig['settings'] ?? [], ['state' => $state]);

        /** @var TypeInterface $type */
        return Craft::createObject($class, [$settings]);
    }

    /**
     * Create an auth state for a scenario and branch.
     *
     * @param string $scenario
     * @param string $branch
     * @return State
     * @throws InvalidConfigException
     */
    public static function createAuthState(string $scenario, string $branch): State
    {
        return Craft::createObject(State::class, [[
            'authenticationScenario' => $scenario,
            'authenticationBranch' => $branch
        ]]);
    }

    /**
     * Get the code authenticator instance.
     *
     * @return Google2FA
     */
    public static function getCodeAuthenticator(): Google2FA
    {
        // TODO window as a config option
        // Probably better as a method on the relevant auth step?
        $authenticator = new Google2FA();
        $authenticator->setWindow(2);

        return $authenticator;
    }
}
