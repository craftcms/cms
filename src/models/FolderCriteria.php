<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;

/**
 * Folders parameters.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FolderCriteria extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int|string|bool Parent ID
     */
    public $parentId = false;

    /**
     * @var int Source ID
     */
    public $volumeId;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Path
     */
    public $path;

    /**
     * @var string Order
     */
    public $order = 'name asc';

    /**
     * @var int Offset
     */
    public $offset;

    /**
     * @var int Limit
     */
    public $limit;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parentId', 'sourceId', 'offset', 'limit'], 'number', 'integerOnly' => true],
        ];
    }
}
