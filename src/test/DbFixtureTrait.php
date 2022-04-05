<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use yii\db\Connection;
use yii\test\DbFixture;

/**
 * trait DbFixtureTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 * @mixin DbFixture
 */
trait DbFixtureTrait
{
    /**
     * @var bool Whether database integrity checks should be explicitly enabled before deleting fixture data,
     * and then re-disabled afterwards.
     */
    public bool $checkIntegrity = true;

    /**
     * Toggles the DB integrity check.
     *
     * @param bool $check whether to turn on or off the integrity check.
     */
    protected function checkIntegrity(bool $check): void
    {
        if (!$this->checkIntegrity || !$this->db instanceof Connection) {
            return;
        }
        $this->db->createCommand()->checkIntegrity($check)->execute();
    }

    /**
     * Hard-deletes everything in the database.
     */
    protected function hardDelete(): void
    {
        $gc = \Craft::$app->getGc();
        $deleteAllTrashed = $gc->deleteAllTrashed;
        $gc->deleteAllTrashed = true;
        $gc->run(true);
        $gc->deleteAllTrashed = $deleteAllTrashed;
    }
}
