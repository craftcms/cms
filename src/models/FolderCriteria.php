<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Folders parameters.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FolderCriteria extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|string|bool Parent ID
     */
    public $parentId = false;

    /**
     * @var int|null Source ID
     */
    public $volumeId;

    /**
     * @var string|string[]|null The folder name(s).
     *
     * ::: tip
     * If you’re searching for a folder name that contains a comma, pass the value through
     * [[\craft\helpers\Db::escapeParam()]] to prevent it from getting treated as multiple folder name values.
     * :::
     */
    public $name;

    /**
     * @var string|null Path
     */
    public $path;

    /**
     * @var string Order
     */
    public $order = 'name asc';

    /**
     * @var int|null Offset
     */
    public $offset;

    /**
     * @var int|null Limit
     */
    public $limit;

    /**
     * @var string|string[]|null
     */
    public $uid;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'parentId', 'sourceId', 'offset', 'limit'], 'number', 'integerOnly' => true];
        return $rules;
    }
}
