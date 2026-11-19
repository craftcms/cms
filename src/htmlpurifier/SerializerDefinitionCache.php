<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\htmlpurifier;

use HTMLPurifier_DefinitionCache_Serializer;

/**
 * HTML Purifier definition cache that writes cache files atomically.
 *
 * HTML Purifier’s serializer writes cache files in place, so another request could read a partial one.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @internal
 */
class SerializerDefinitionCache extends HTMLPurifier_DefinitionCache_Serializer
{
    /**
     * @inheritdoc
     */
    public function add($def, $config)
    {
        if (!$this->checkDefType($def)) {
            return false;
        }

        // add() is only called after a cache miss, so overwrite any existing file in case it was unreadable
        $file = $this->generateFilePath($config);
        $dir = dirname($file);
        $chmod = $config->get('Cache.SerializerPermissions');

        if (!is_dir($dir) && !@mkdir($dir, $chmod ?? 0777, true) && !is_dir($dir)) {
            return false;
        }

        // Start the temp file name with a dot, so cleanup() and flush() ignore it
        $tmpFile = sprintf('%s/.%s.%s', $dir, basename($file), bin2hex(random_bytes(4)));
        $result = @file_put_contents($tmpFile, serialize($def));

        if ($result === false) {
            return false;
        }

        if ($chmod !== null) {
            @chmod($tmpFile, $chmod & 0666);
        }

        if (!@rename($tmpFile, $file)) {
            @unlink($tmpFile);
            return false;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function cleanup($config)
    {
        // HTML Purifier runs this on every cache miss. Its warnings, such as when another request already
        // removed an old file, shouldn't fail the request.
        return @parent::cleanup($config);
    }

    /**
     * @inheritdoc
     */
    public function get($config)
    {
        $file = $this->generateFilePath($config);
        if (!file_exists($file)) {
            return false;
        }

        // HTML Purifier’s own serializer may still be writing the file, so treat an unreadable one as a cache miss
        $data = @file_get_contents($file);
        return ($data !== false ? @unserialize($data) : false) ?: false;
    }
}
