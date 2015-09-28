<?php
namespace craft\app\io\flysystemadapters;

use \League\Flysystem\AwsS3v2\AwsS3Adapter;

/**
 * Amazon S3 Flysystem adapter class
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

        return $this->delete($path);
    }
}
