<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache as CacheFacade;

class Cache extends \yii\caching\Cache
{
    private Repository $laravelCache;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->laravelCache ??= CacheFacade::store();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
    {
        return $this->laravelCache->get($key, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $duration): bool
    {
        $this->laravelCache->put($key, $value, $this->convertDuration($duration));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function addValue($key, $value, $duration): bool
    {
        return $this->laravelCache->add($key, $value, $this->convertDuration($duration));
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key): bool
    {
        return $this->laravelCache->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function flushValues(): bool
    {
        return $this->laravelCache->clear();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues($keys): iterable
    {
        return $this->laravelCache->getMultiple($keys, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValues($data, $duration): array
    {
        $this->laravelCache->setMultiple($data, $this->convertDuration($duration));

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function addValues($data, $duration): array
    {
        $values = $this->multiGet(array_keys($data));

        $failedKeys = [];
        $newValues = [];

        foreach ($values as $key => $value) {
            if ($value !== false) {
                $failedKeys[] = $key;

                continue;
            }

            $newValues[$key] = $data[$key];
        }

        $this->setValues($newValues, $duration);

        return $failedKeys;
    }

    protected function convertDuration(float|int|null $duration): float|int|null
    {
        return ((int) $duration === 0) ? null : $duration;
    }
}
