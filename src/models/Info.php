<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

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
}
