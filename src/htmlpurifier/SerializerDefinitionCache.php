<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\htmlpurifier;

use HTMLPurifier_DefinitionCache_Serializer;

/**
 * HTML Purifier definition cache that treats unreadable cache files as cache misses.
 *
 * HTML Purifier writes cache files in place, so a request can read one that another request
 * is still writing.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @internal
 */
class SerializerDefinitionCache extends HTMLPurifier_DefinitionCache_Serializer
{
    public function get($config)
    {
        $file = $this->generateFilePath($config);
        if (!file_exists($file)) {
            return false;
        }

        $data = @file_get_contents($file);
        $def = $data !== false ? @unserialize($data) : false;

        if (!$def) {
            // Remove it, so a request can write a complete one
            @unlink($file);
            return false;
        }

        return $def;
    }
}
