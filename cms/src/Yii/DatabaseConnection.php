<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Craft\Cms\Yii;

use craft\db\Connection;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Facades\DB;

class DatabaseConnection extends Connection
{
    private ?IlluminateConnection $laravelConnection = null;

    /**
     * {@inheritdoc}
     */
    public function open(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $this->pdo = $this->getLaravelConnection()->getPdo();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->pdo === null) {
            return;
        }

        $this->getLaravelConnection()->disconnect();

        $this->pdo = null;
    }

    public function getLaravelConnection(): IlluminateConnection
    {
        return $this->laravelConnection ??= DB::connection();
    }
}
