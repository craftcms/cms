<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * Class Info model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Info extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var string Version
     */
    public $version;

    /**
     * @var string Schema version
     */
    public $schemaVersion = '0';

    /**
     * @var integer Edition
     */
    public $edition = \Craft::Personal;

    /**
     * @var string Timezone
     */
    public $timezone = 'America/Los_Angeles';

    /**
     * @var boolean On
     */
    public $on = false;

    /**
     * @var boolean Maintenance
     */
    public $maintenance = false;

    /**
     * @var string Uid
     */
    public $uid;

    /**
     * @var string Field version
     */
    public $fieldVersion;

    /**
     * @var \DateTime Date updated
     */
    public $dateUpdated;

    /**
     * @var \DateTime Date created
     */
    public $dateCreated;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Make sure $edition is going to be an integer
        if (isset($this->edition)) {
            $this->edition = (int)$this->edition;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'edition'], 'number', 'integerOnly' => true],
            [['version', 'schemaVersion', 'edition'], 'required'],
            [['timezone'], 'string', 'max' => 30],
        ];
    }
}
