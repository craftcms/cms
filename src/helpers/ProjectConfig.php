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
        $map = [];

        $traverseAndExtract = function ($config, $prefix, &$map) use (&$traverseAndExtract) {
            foreach ($config as $key => $value) {
                // Does it look like a UID?
                if (preg_match('/'.ProjectConfigService::UID_PATTERN.'/i', $key)) {
                    $map[$key] = $prefix.'.'.$key;
                }

                if (\is_array($value)) {
                    $traverseAndExtract($value, $prefix.(substr($prefix, -1) !== '/' ? '.' : '').$key, $map);
                }
            }
        };

        foreach ($fileList as $file) {
            $config = Yaml::parseFile($file);

            // Take record of top nodes
            $topNodes = array_keys($config);
            foreach ($topNodes as $topNode) {
                $nodes[$topNode] = $file;
            }

            $traverseAndExtract($config, $file.'/', $map);
        }

        unset($nodes['imports']);

        return [
            'nodes' => $nodes,
            'map' => $map
        ];
    }

    /**
     * Extract dependencies from an array of data.
     *
     * @param array $data
     * @return array
     */
    public static function getDependencies(array $data)
    {
        $traverse = function ($data) use (&$traverse) {
            $dependencies = [];
            foreach ($data as $key => $value) {
                if ($key === 'dependsOn') {
                    if (is_array($value)) {
                        $dependencies = array_merge($dependencies, $value);
                    } else {
                        $dependencies[] = $value;
                    }
                } else if (is_array($value)) {
                    $dependencies = array_merge($dependencies, $traverse($value));
                }
            }
            return $dependencies;
        };

        return $traverse($data);
    }
}
