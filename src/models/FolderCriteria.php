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
    public ?int $id = null;

    /**
     * @var int|string|null Parent ID
     */
    public string|int|null $parentId = null;

    /**
     * @var int|string|null Source ID
     */
    public string|int|null $volumeId = null;

    /**
     * @var string|string[]|null The folder name(s).
     *
     * ::: tip
     * If you’re searching for a folder name that contains a comma, pass the value through
     * [[\craft\helpers\Db::escapeParam()]] to prevent it from getting treated as multiple folder name values.
     * :::
     */
    public array|string|null $name = null;

    /**
     * @var string|null Path
     */
    public ?string $path = null;

    /**
     * @var string Order
     */
    public string $order = 'name asc';

    /**
     * @var int|null Offset
     */
    public ?int $offset = null;

    /**
     * @var int|null Limit
     */
    public ?int $limit = null;

    /**
     * @var string|string[]|null
     */
    public array|string|null $uid = null;

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
