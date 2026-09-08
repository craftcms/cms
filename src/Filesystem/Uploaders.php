<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Uploaders\ChunkedUploader;
use CraftCms\Cms\Filesystem\Uploaders\S3Uploader;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Manager;
use Override;

#[Singleton]
class Uploaders extends Manager
{
    public function getDefaultDriver(): string
    {
        if (Cms::config()->uploader !== null) {
            return Cms::config()->uploader;
        }

        $disk = $this->container->make(Filesystems::class)->disk(
            Cms::config()->getTempAssetUploadFs(),
        );

        return $disk instanceof AwsS3V3Adapter ? 's3' : 'chunked';
    }

    #[Override]
    public function driver($driver = null): Uploader
    {
        return parent::driver($driver);
    }

    protected function createChunkedDriver(): Uploader
    {
        return $this->container->make(ChunkedUploader::class);
    }

    protected function createS3Driver(): Uploader
    {
        return $this->container->make(S3Uploader::class);
    }
}
