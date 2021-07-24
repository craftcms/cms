<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\db\MigrationManager;
use yii\base\Event;

/**
 * RegisterMigratorEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class RegisterMigratorEvent extends Event
{
    /**
     * @var string The requested migration track
     */
    public string $track;

    /**
     * @var MigrationManager|null $migrator The migration manager to use
     */
    public ?MigrationManager $migrator = null;
}
