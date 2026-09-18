<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Uploaders\S3Uploader;
use CraftCms\Cms\Filesystem\Uploaders\TusUploader;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Manager;
use Override;

#[Singleton]
class Uploaders extends Manager
{
    public function getDefaultDriver(?string $diskName = null): string
    {
        if (Cms::config()->uploader !== null) {
            return Cms::config()->uploader;
        }

        $disk = Storage::disk($diskName ?? Cms::config()->getUploadSessionDisk());

        return $disk instanceof AwsS3V3Adapter ? 's3' : 'tus';
    }

    #[Override]
    public function driver($driver = null): Uploader
    {
        return parent::driver($driver);
    }

    protected function createTusDriver(): Uploader
    {
        return $this->container->make(TusUploader::class);
    }

    protected function createS3Driver(): Uploader
    {
        return $this->container->make(S3Uploader::class);
    }
}
