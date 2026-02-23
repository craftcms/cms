<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter\Web;

use ArrayIterator;
use Illuminate\Session\Store;
use yii\base\Component;

/**
 * Session allows usage of the Laravel Session for Yii one.
 *
 * This class allows sharing session data between Laravel and Yii, preserving authentication state loss and
 * avoiding session data loss.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'session' => CraftCms\Cms\Yii\Web\Session::class,
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * > Note: usage of this component requires Yii application running within {@see \Illuminate\Session\Middleware\StartSession} middleware.
 *
 * @see \Illuminate\Session\Store
 *
 * @property \Illuminate\Session\Store $illuminateSession related Laravel session instance.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 *
 * @since 1.0
 */
class Session extends \yii\web\Session
{
    /**
     * {@inheritdoc}
     */
    public $flashParam = '__yii_flash';

    private ?Store $_illuminateSession = null;

    private bool $_flashCountersUpdated = false;

    public function getIlluminateSession(): Store
    {
        if (!is_null($this->_illuminateSession)) {
            return $this->_illuminateSession;
        }

        /** @var Store $store */
        $store = session()->driver();

        if ($store->handlerNeedsRequest()) {
            $store->setRequestOnHandler(request());
        }

        $this->_illuminateSession = $store;

        return $store;
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        if ($this->getIsActive()) {
            $this->updateFlashCounters();
        }

        // skip parent init, avoiding `register_shutdown_function()` call.
        Component::init();
    }

    /**
     * {@inheritdoc}
     */
    public function open(): void
    {
        if ($this->getIsActive()) {
            return;
        }

        $this->getIlluminateSession()->start();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (!$this->getIsActive()) {
            return;
        }

        $this->getIlluminateSession()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(): void
    {
        if (!$this->getIsActive()) {
            return;
        }

        $this->getIlluminateSession()->invalidate();
    }

    /**
     * {@inheritdoc}
     */
    public function getIsActive(): bool
    {
        return $this->getIlluminateSession()->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->getIlluminateSession()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setId($value): void
    {
        $this->getIlluminateSession()->setId($value);
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateID($deleteOldSession = false): void
    {
        if (!$this->getIsActive()) {
            return;
        }

        $this->getIlluminateSession()->regenerate($deleteOldSession);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getIlluminateSession()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setName($value): void
    {
        $this->getIlluminateSession()->setName($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->open();

        /** @phpstan-ignore return.type */
        return new ArrayIterator($this->getIlluminateSession()->all());
    }

    /**
     * {@inheritdoc}
     */
    public function getCount(): int
    {
        $this->open();

        return count($this->getIlluminateSession()->all());
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        $this->open();

        return $this->getIlluminateSession()->get($key, $defaultValue);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->open();

        $this->getIlluminateSession()->put($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->open();

        return $this->getIlluminateSession()->pull($key);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll(): void
    {
        $this->open();
        $this->getIlluminateSession()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        $this->open();

        return $this->getIlluminateSession()->has($key);
    }

    // Flash :

    /**
     * Ensures flash counters have been updated for this request.
     *
     * If {@see init()} ran before the Laravel session was started,
     * the counters were never aged. This method ensures they are
     * aged exactly once before any flash data is read.
     */
    private function ensureFlashCountersUpdated(): void
    {
        if ($this->_flashCountersUpdated) {
            return;
        }

        if ($this->getIsActive()) {
            $this->updateFlashCounters();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateFlashCounters(): void
    {
        $this->_flashCountersUpdated = true;

        $counters = $this->get($this->flashParam, []);

        if (!is_array($counters)) {
            // fix the unexpected problem that flashParam doesn't return an array
            $this->remove($this->flashParam);

            return;
        }

        foreach ($counters as $key => $count) {
            if ($count > 0) {
                unset($counters[$key]);
                $this->remove($key);
            } elseif ((int) $count === 0) {
                $counters[$key]++;
            }
        }

        $this->set($this->flashParam, $counters);
    }

    /**
     * {@inheritdoc}
     */
    public function getFlash($key, $defaultValue = null, $delete = false)
    {
        $this->ensureFlashCountersUpdated();

        $counters = $this->get($this->flashParam, []);

        if (!isset($counters[$key])) {
            return $defaultValue;
        }

        $value = $this->get($key, $defaultValue);

        if ($delete) {
            $this->removeFlash($key);
        } elseif ($counters[$key] < 0) {
            // mark for deletion in the next request
            $counters[$key] = 1;
            $this->set($this->flashParam, $counters);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllFlashes($delete = false): array
    {
        $this->ensureFlashCountersUpdated();

        $counters = $this->get($this->flashParam, []);
        $flashes = [];

        $session = $this->getIlluminateSession()->all();

        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $session)) {
                $flashes[$key] = $session[$key];
                if ($delete) {
                    unset($counters[$key], $session[$key]);
                    $this->remove($key);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    $counters[$key] = 1;
                }
            } else {
                unset($counters[$key]);
            }
        }

        $this->set($this->flashParam, $counters);

        return $flashes;
    }

    /**
     * {@inheritdoc}
     */
    public function setFlash($key, $value = true, $removeAfterAccess = true): void
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;

        $this->set($key, $value);
        $this->set($this->flashParam, $counters);
    }

    /**
     * {@inheritdoc}
     */
    public function addFlash($key, $value = true, $removeAfterAccess = true): void
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;

        $this->set($this->flashParam, $counters);
        $session = $this->getIlluminateSession()->all();

        if (empty($session[$key])) {
            $session[$key] = [$value];
        } elseif (is_array($session[$key])) {
            $session[$key][] = $value;
        } else {
            $session[$key] = [$session[$key], $value];
        }

        $this->set($key, $session[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeFlash($key)
    {
        $counters = $this->get($this->flashParam, []);
        $session = $this->getIlluminateSession()->all();
        $value = isset($session[$key], $counters[$key]) ? $session[$key] : null;
        unset($counters[$key]);
        $this->remove($key);
        $this->set($this->flashParam, $counters);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllFlashes(): void
    {
        $counters = $this->get($this->flashParam, []);

        foreach (array_keys($counters) as $key) {
            $this->remove($key);
        }

        $this->remove($this->flashParam);
    }

    /**
     * {@inheritdoc}
     */
    public function hasFlash($key): bool
    {
        return $this->getFlash($key) !== null;
    }

    // ArrayAccess :

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        $this->open();
        $session = $this->getIlluminateSession()->all();

        return isset($session[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->open();

        return $this->getIlluminateSession()->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $item): void
    {
        $this->open();

        $this->getIlluminateSession()->put($offset, $item);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->open();

        $this->getIlluminateSession()->forget($offset);
    }
}
