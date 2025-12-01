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
     * @var int|string|array|null ID
     */
    public mixed $id = null;

    /**
     * @var int|string|array|null Parent ID
     */
    public mixed $parentId = null;

    /**
     * @var int|string|array|null Volume ID
     */
    public mixed $volumeId = null;

    /**
     * @var int|string|array|null The folder name(s).
     *
     * ::: tip
     * If you’re searching for a folder name that contains a comma, pass the value through
     * [[\craft\helpers\Db::escapeParam()]] to prevent it from getting treated as multiple folder name values.
     * :::
     */
    public mixed $name = null;

    /**
     * @var string|array|null Path
     */
    public mixed $path = null;

    /**
     * @var string|array Order
     */
    public string|array $order = 'name asc';

    /**
     * @var int|null Offset
     */
    public ?int $offset = null;

    /**
     * @var int|null Limit
     */
    public ?int $limit = null;

    /**
     * @var string|array|null
     */
    public mixed $uid = null;

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
