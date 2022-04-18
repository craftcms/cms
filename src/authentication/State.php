<?php

declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\authentication;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\base\Model;
use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

/**
 * Authentication state model class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read array $alternateSteps
 * @property-read User|null $authenticatedUser
 * @property-read string $nextStep
 * @property-read bool $isAuthenticated
 */
class State extends Model
{
    /**
     * @var User|null The user being authenticated.
     */
    protected ?User $user = null;

    /**
     * @var string[] The available encrypted auth paths.
     */
    protected array $encryptedAuthPaths;

    /**
     * @var array The auth flow configuration being used.
     */
    protected array $authFlow;

    /**
     * @var string Current auth path in the flow.
     */
    protected string $authPath = '0';

    /**
     * @var bool Whether the current user is authenticated
     */
    protected bool $isAuthenticated = false;

    /**
     * @var array<string, TypeInterface> a list of instantiated types
     */
    private $_typeInstances = [];

    /**
     * Construct a new authentication state.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->authFlow = $config['authFlow'] ?? Craft::$app->getAuthentication()->getAuthFlow($this->user);

        parent::__construct($config);
    }

    /**
     * Prepare for serialization by switching the user out for a user id to keep it light and avoid any serialization errors
     * if some 3rd party plugin is adding some stuff in there that we don't account for when unserializing.
     *
     * @return array
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        $vars['userId'] = $vars['user']->id ?? null;
        unset($vars['user']);

        return $vars;
    }

    /**
     * Unserialize and find the user by its id
     * @param array $data
     */
    public function __unserialize(array $data)
    {
        $userId = ArrayHelper::remove($data, 'userId');

        foreach ($data as $prop => $value) {
            $this->{$prop} = $value;
        }

        if ($userId) {
            $this->setUser(User::findOne($userId));
        }

        if (ArrayHelper::getValue($this->authFlow, $this->authPath) === null) {
            $this->authPath = '0';
        }
    }

    /**
     * Set the active user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->authFlow = Craft::$app->getAuthentication()->getAuthFlow($this->user);
    }

    /**
     * Get the user for whom the authentication is in progress.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Get the next authentication step to execute.
     *
     * @return TypeInterface
     * @throws AuthenticationException if unable to resolve the next auth step
     */
    public function getNextStep(): TypeInterface
    {
        $typeName = ArrayHelper::getValue($this->authFlow, $this->authPath, null)['type'] ?? null;

        if (!$typeName || !is_subclass_of($typeName, TypeInterface::class)) {
            throw new AuthenticationException('Unable to resolve next authentication step');
        }

        $type = Craft::createObject($typeName);
        $type->setState($this);

        return $type;
    }

    /**
     * Get the encrypted paths for next alternate steps to take.
     *
     * @return array
     */
    public function getAlternativeSteps(): array
    {
        $alternateSteps = [];
        $alternateTypes = [];
        $stepCollection = $this->authFlow;

        $parts = explode('.', $this->authPath);
        $path = '';


        foreach ($parts as $partIndex => $pathPart) {
            // If we are at a top level choice, look at all possible top level actions.
            $stepCollection = $stepCollection[$pathPart];
            $path .= StringHelper::removeLeft($pathPart . '.', '.');

            foreach ($stepCollection as $pathSegment => $possibleChoice) {
                $lookAhead = $parts[$partIndex + 1] ?? '';
                if ((string)$pathSegment === (string)$lookAhead) {
                    continue;
                }

                if (!empty($possibleChoice['type'])) {
                    $alternateTypes[$path . $pathSegment] = $possibleChoice['type'];
                }
            }
        }

        $pathPart = $parts[0];
        foreach ($this->authFlow as $pathSegment => $possibleChoice) {
            if ((string)$pathSegment === (string)$pathPart) {
                continue;
            }

            if (!empty($possibleChoice['type'])) {
                $alternateTypes[$pathSegment] = $possibleChoice['type'];
            }
        }

        foreach ($alternateTypes as $path => $type) {
            $encryptedPath = uniqid('auth_', true);
            $this->encryptedAuthPaths[$encryptedPath] = $path;
            $alternateSteps[$encryptedPath] = method_exists($type, 'displayName') ? $type::displayName() : $type;
        }

        return $alternateSteps;
    }

    /**
     * Select an alternate authentication step based on an encrypted string.
     *
     * @param string $encryptedString
     * @return bool
     */
    public function selectAlternateStep(string $encryptedString): bool
    {
        $path = $this->encryptedAuthPaths[$encryptedString] ?? null;

        // Can be '0'
        if ($path === null) {
            return false;
        }

        $this->authPath = (string)$path;

        return true;
    }

    /**
     * Complete the current authentication step
     */
    public function completeStep(): void
    {
        $step = ArrayHelper::getValue($this->authFlow, $this->authPath);

        if (empty($step['then'])) {
            $this->isAuthenticated = true;
        } else {
            $this->authPath .= '.then.0';
        }
    }

    /**
     * Return `true` if the user is considered fully authenticated.
     *
     * @return bool
     */
    public function getIsAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }

    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        return $this->getIsAuthenticated() ? $this->user : null;
    }
}
