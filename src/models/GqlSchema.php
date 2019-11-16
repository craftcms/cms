<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\Json;
use craft\records\GqlSchema as GqlSchemaRecord;
use craft\validators\UniqueValidator;

/**
 * GraphQL schema class
 *
 * @property bool $isPublic Whether this is the public schema
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GqlSchema extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string Schema name
     */
    public $name;

    /**
     * @var array The schema’s scope
     */
    public $scope = [];

    /**
     * @var string $uid
     */
    public $uid;

    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);

        if (is_string($this->scope)) {
            $this->scope = Json::decodeIfJson($this->scope);
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['name'], 'required'];
        $rules[] = [
            ['name'],
            UniqueValidator::class,
            'targetClass' => GqlSchemaRecord::class,
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
     * Return whether this schema can perform an action
     *
     * @param $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return is_array($this->scope) && in_array($name, $this->scope, true);
    }
}
