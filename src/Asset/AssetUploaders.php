<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetUploader;
use CraftCms\Cms\Asset\Uploaders\ChunkedUploader;
use CraftCms\Cms\Asset\Uploaders\S3Uploader;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Filesystems;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Manager;
use Override;

#[Singleton]
class AssetUploaders extends Manager
{
    public function getDefaultDriver(): string
    {
        if (Cms::config()->assetUploader !== null) {
            return Cms::config()->assetUploader;
        }

        $disk = $this->container->make(Filesystems::class)->disk(
            Cms::config()->getTempAssetUploadFs(),
        );

        return $disk instanceof AwsS3V3Adapter ? 's3' : 'chunked';
    }

    #[Override]
    public function driver($driver = null): AssetUploader
    {
        return parent::driver($driver);
    }

    protected function createChunkedDriver(): AssetUploader
    {
        return $this->container->make(ChunkedUploader::class);
    }

    protected function createS3Driver(): AssetUploader
    {
        return $this->container->make(S3Uploader::class);
    }
}
