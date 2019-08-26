<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\Json;
use craft\records\GqlToken as GqlTokenRecord;
use craft\validators\UniqueValidator;

/**
 * GQL token class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GqlToken extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string Token name
     */
    public $name;

    /**
     * @var string The access token
     */
    public $accessToken;

    /**
     * @var bool Is the token enabled
     */
    public $enabled = true;

    /**
     * @var \DateTime|null Date expires
     */
    public $expiryDate;

    /**
     * @var \DateTime|null Date last used
     */
    public $lastUsed;

    /**
     * @var array Permissions
     */
    public $permissions = [];

    /**
     * @var \DateTime|null Date created
     */
    public $dateCreated;

    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);

        if (is_string($this->permissions)) {
            $this->permissions = Json::decodeIfJson($this->permissions);
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'expiryDate';
        $attributes[] = 'lastUsed';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['name', 'accessToken'], 'required'];
        $rules[] = [
            ['name', 'accessToken'],
            UniqueValidator::class,
            'targetClass' => GqlTokenRecord::class,
        ];

        return $rules;
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Return whether this token can perform an action
     *
     * @param $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        return is_array($this->permissions) && in_array($permissionName, $this->permissions, true);
    }
}
