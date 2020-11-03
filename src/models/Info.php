<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;

/**
 * Class Info model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Info extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Version
     */
    public $version;

    /**
     * @var string Schema version
     */
    public $schemaVersion = '0';

    /**
     * @var bool Maintenance
     */
    public $maintenance = false;

    /**
     * @var string|null Uid
     */
    public $uid;

    /**
     * @var string Field version
     * @since 3.5.6
     */
    public $configVersion = '000000000000';

    /**
     * @var string Field version
     */
    public $fieldVersion = '000000000000';

    /**
     * @var \DateTime|null Date updated
     */
    public $dateUpdated;

    /**
     * @var \DateTime|null Date created
     */
    public $dateCreated;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['version', 'schemaVersion'], 'required'];
        return $rules;
    }

    // Deprecated
    // -------------------------------------------------------------------------

    /**
     * Returns the active Craft edition.
     *
     * @return int
     * @deprecated in 3.1.0. Use `Craft::$app->getEdition()` instead.
     */
    public function getEdition(): int
    {
        return Craft::$app->getEdition();
    }

    /**
     * Returns the system name.
     *
     * @return string
     * @deprecated in 3.1.0. Use `Craft::$app->getSystemName()` instead.
     */
    public function getName(): string
    {
        return Craft::$app->getSystemName();
    }

    /**
     * Returns the system time zone.
     *
     * @return string
     * @deprecated in 3.1.0. Use `Craft::$app->getTimeZone()` instead.
     */
    public function getTimezone(): string
    {
        return Craft::$app->getTimeZone();
    }

    /**
     * Returns whether the system is currently live.
     *
     * @return bool
     * @deprecated in 3.1.0. Use `Craft::$app->getIsLive()` instead.
     */
    public function getOn(): bool
    {
        return Craft::$app->getIsLive();
    }
}
