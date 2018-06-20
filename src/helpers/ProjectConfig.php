<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\services\ProjectConfig as ProjectConfigService;
use Symfony\Component\Yaml\Yaml;


/**
 * Class ProjectConfig
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfig
{
    // Public Methods
    // =========================================================================

    /**
     * Generate the configuration map from an array of YAML files.
     * This function will ignore any `import` directives.
     *
     * @param array $fileList
     * @return array
     */
    public static function generateConfigMap(array $fileList): array
    {
        $nodes = [];

        foreach ($fileList as $file) {
            $config = Yaml::parseFile($file);

            // Take record of top nodes
            $topNodes = array_keys($config);
            foreach ($topNodes as $topNode) {
                $nodes[$topNode] = $file;
            }

        }

        unset($nodes['imports']);

        return $nodes;
    }
}
