<?php
namespace craft\app\io\flysystemadapters;

use \League\Flysystem\AwsS3v2\AwsS3Adapter;

/**
 * Google Cloud Flysystem adapter class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.io.flysystemadapters
 * @since     3.0
 */
class GoogleCloud extends AwsS3Adapter implements IFlysystemAdapter
{
    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        $path = rtrim($this->applyPathPrefix($path), '/').'/';

        $success = true;

        $objects = $this->listContents($path);
        $directoryList = [];

        foreach ($objects as $object)
        {
            if ($object['type'] == 'dir')
            {
                $directoryList[] = $object['path'];
            }
            else
            {
                $success = $success && $this->delete($object['path']);
            }
        }

        foreach ($directoryList as $directoryPath)
        {
            $directoryPath = rtrim($this->applyPathPrefix($directoryPath), '/').'/';

            // This operation can return false as well, if the directory was not
            // an object istelf but only part of the path for the files.
            $this->delete($directoryPath);
        }

        return $success;
    }
}
