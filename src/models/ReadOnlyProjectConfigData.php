<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;

/**
 * ReadOnlyProjectConfigData model class represents an instance of a project config data structure that cannot be modified
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ReadOnlyProjectConfigData extends Model
{
    protected array $data;

    /** @since 4.4.17 */
    protected ProjectConfig $projectConfig;

    public function __construct(array $data = [], ?ProjectConfig $projectConfig = null, array $config = [])
    {
        $this->data = $data;
        $this->projectConfig = $projectConfig ?? Craft::$app->getProjectConfig();

        parent::__construct($config);
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
