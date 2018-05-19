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
 * @since 3.0
 */
class Info extends Model
{
    // Properties
    // =========================================================================

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
     * @var int Edition
     */
    public $edition = Craft::Solo;

    /**
     * @var string System name
     */
    public $name = '';

    /**
     * @var string Timezone
     */
    public $timezone = 'America/Los_Angeles';

    /**
     * @var bool On
     */
    public $on = false;

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Make sure $edition is going to be an int
        if (is_string($this->edition)) {
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
            [['version', 'schemaVersion', 'edition', 'name'], 'required'],
            [['timezone'], 'string', 'max' => 30],
        ];
    }
}
