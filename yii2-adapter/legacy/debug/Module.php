<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\web\View;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use yii\base\InvalidConfigException;

/**
 * The Yii Debug Module provides the debug toolbar and debugger
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class Module extends \yii\debug\Module
{
    /**
     * @var FsInterface|FilesystemAdapter|string|array|null The filesystem or disk config that debug cache files should be stored in.
     *
     * @since 4.0.0
     */
    public FsInterface|FilesystemAdapter|string|array|null $fs = null;

    public ?FilesystemAdapter $disk = null;

    /**
     * {@inheritdoc}
     */
    public function bootstrap($app): void
    {
        $this->disk = $this->resolveDisk();

        if ($this->disk && !$this->disk->directoryExists($this->dataPath)) {
            $this->disk->makeDirectory($this->dataPath);
        }

        parent::bootstrap($app);

        $this->logTarget = $app->getLog()->targets['debug'] = new LogTarget($this);
    }

    /**
     * {@inheritdoc}
     */
    public function renderToolbar($event): void
    {
        if (!$this->checkAccess() || Craft::$app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->sender;
        echo $this->getToolbarHtml();

        echo '<style>' . $view->renderPhpFile($this->getBasePath() . '/assets/css/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile($this->getBasePath() . '/assets/js/toolbar.js') . '</script>';
    }

    private function resolveDisk(): ?FilesystemAdapter
    {
        if ($this->fs === null) {
            return null;
        }

        if ($this->fs instanceof FilesystemAdapter) {
            return $this->fs;
        }

        if ($this->fs instanceof FsInterface) {
            if ($this->fs instanceof DiskFileSystem && is_string($this->fs->disk) && $this->fs->disk !== '') {
                return Storage::disk($this->fs->disk);
            }

            return $this->resolveDiskFromHandle($this->fs->handle ?? null);
        }

        if (is_string($this->fs)) {
            return $this->resolveDiskFromHandle($this->fs);
        }

        if (is_array($this->fs)) {
            if (isset($this->fs['disk']) && is_string($this->fs['disk']) && $this->fs['disk'] !== '') {
                return Storage::disk($this->fs['disk']);
            }

            if (isset($this->fs['handle']) && is_string($this->fs['handle'])) {
                return $this->resolveDiskFromHandle($this->fs['handle']);
            }
        }

        throw new InvalidConfigException('Invalid debug filesystem configuration.');
    }

    private function resolveDiskFromHandle(?string $handle): FilesystemAdapter
    {
        $handle = $handle !== null ? (Env::parse($handle) ?? '') : '';

        if ($handle === '') {
            throw new InvalidConfigException('Invalid debug filesystem handle.');
        }

        if (str_starts_with($handle, 'disk:')) {
            $diskName = substr($handle, strlen('disk:'));
            if ($diskName !== '' && is_array(config('filesystems.disks')) && array_key_exists($diskName, config('filesystems.disks'))) {
                return Storage::disk($diskName);
            }

            throw new InvalidConfigException("Invalid debug filesystem handle: $handle");
        }

        $disks = config('filesystems.disks', []);
        if (is_array($disks) && array_key_exists($handle, $disks)) {
            return Storage::disk($handle);
        }

        if (Filesystems::getFilesystemByHandle($handle)) {
            return Filesystems::disk($handle);
        }

        throw new InvalidConfigException("Invalid debug filesystem handle: $handle");
    }
}
