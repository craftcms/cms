<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

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
    public ?int $id = null;

    /**
     * @var string|null Version
     */
    public ?string $version = null;

    /**
     * @var string Schema version
     */
    public string $schemaVersion = '0';

    /**
     * @var bool Maintenance
     */
    public bool $maintenance = false;

    /**
     * @var string|null Uid
     */
    public ?string $uid = null;

    /**
     * @var string Field version
     * @since 3.5.6
     */
    public string $configVersion = '000000000000';

    /**
     * @var string|null Field version
     */
    public ?string $fieldVersion = null;

    /**
     * @var DateTime|null Date updated
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var DateTime|null Date created
     */
    public ?DateTime $dateCreated = null;

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
