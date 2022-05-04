<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\ProjectConfig as ProjectConfigHelper;

/**
 * ReadOnlyProjectConfigData model class represents an instance of a project config data structure that cannot be modified
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ReadOnlyProjectConfigData extends Model
{
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        parent::__construct();
    }

    /**
     * Get a stored data value for a path.
     *
     * @param string|string[] $path
     * @return mixed
     */
    public function get(array|string $path): mixed
    {
        return ProjectConfigHelper::traverseDataArray($this->data, $path);
    }

    /**
     * Export the data to an array.
     *
     * @return array
     */
    public function export(): array
    {
        return $this->data;
    }
}
