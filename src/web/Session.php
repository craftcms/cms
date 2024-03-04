<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use craft\behaviors\SessionBehavior;

/**
 * Extends [[\yii\web\Session]] to add support for setting the session folder and creating it if it doesn’t exist.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @mixin SessionBehavior
 */
class Session extends \yii\web\Session
{
    /**
     * @inheritdoc
     *
     * ---
     *
     * ```php
     * $message = Craft::$app->session->getFlash('notice', null, true);
     * ```
     * ```twig{1}
     * {% set message = craft.app.session.getFlash('notice', null, true) %}
     * {% if message %}
     *   <p class="notice">
     *     {{ message }}
     *   </p>
     * {% endif %}
     * ```
     */
    public function getFlash($key, $defaultValue = null, $delete = false)
    {
        return parent::getFlash($key, $defaultValue, $delete);
    }

    /**
     * @inheritdoc
     *
     * ---
     *
     * ```php
     * $messages = Craft::$app->session->getAllFlashes(true);
     * ```
     * ```twig{1}
     * {% set messages = craft.app.session.getAllFlashes(true) %}
     * {% for key, message in messages %}
     *   <p class="{{ key }}">
     *     {{ message }}
     *   </p>
     * {% endfor %}
     * ```
     */
    public function getAllFlashes($delete = false): array
    {
        return parent::getAllFlashes($delete);
    }

    /**
     * @inheritdoc
     *
     * ---
     *
     * ```php
     * $hasNotice = Craft::$app->session->hasFlash('notice');
     * ```
     * ```twig{1}
     * {% if craft.app.session.hasFlash('notice') %}
     *   <p class="notice">
     *     {{ craft.app.session.getFlash('notice', null, true) }}
     *   </p>
     * {% endif %}
     * ```
     */
    public function hasFlash($key): bool
    {
        return parent::hasFlash($key);
    }

    public function getCount(): int
    {
        return $this->getIsActive()
            ? parent::getCount()
            : 0;
    }

    public function get($key, $defaultValue = null)
    {
        return $this->getIsActive()
            ? parent::get($key, $defaultValue)
            : null;
    }

    public function remove($key)
    {
        return $this->getIsActive()
            ? parent::remove($key)
            : null;
    }

    public function removeAll(): void
    {
        if (!$this->getIsActive()) {
            return;
        }

        parent::removeAll();
    }

    public function has($key): bool
    {
        return $this->getIsActive() && parent::has($key);
    }
}
