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
     * @var bool Maintenance
     */
    public $maintenance = false;

    /**
     * @var string Serialized configuration
     */
    public $config = '';

    /**
     * @var string JSON array of configuration map of UIDs to location in configuration
     */
    public $configMap = '';

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
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['version', 'schemaVersion'], 'required'],
        ];
    }
}
