<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Stores all of the available update info.
 *
 * @property bool $hasCritical Whether any of the updates have a critical release available
 * @property int $total The total number of available updates
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Updates extends Model
{
    /**
     * @var Update CMS update info
     */
    public $cms;

    /**
     * @var Update[] Plugin update info
     */
    public $plugins = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->cms instanceof Update) {
            $this->cms = new Update($this->cms ?? []);
        }

        foreach ($this->plugins as $handle => $plugin) {
            if (!$plugin instanceof Update) {
                $this->plugins[$handle] = new Update($plugin);
            }
        }
    }

    /**
     * Returns the total number of available updates.
     *
     * @return int
     */
    public function getTotal(): int
    {
        $count = 0;

        if ($this->cms->getHasReleases()) {
            $count++;
        }

        foreach ($this->plugins as $update) {
            if ($update->getHasReleases()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns whether any of the updates have a critical release available.
     *
     * @return bool
     */
    public function getHasCritical(): bool
    {
        if ($this->cms->getHasCritical()) {
            return true;
        }
        foreach ($this->plugins as $plugin) {
            if ($plugin->getHasCritical()) {
                return true;
            }
        }
        return false;
    }
}
