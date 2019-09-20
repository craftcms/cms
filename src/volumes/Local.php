<?php

namespace craft\volumes;

use Craft;
use craft\base\FlysystemVolume;
use craft\base\LocalVolumeInterface;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\helpers\FileHelper;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;

/**
 * The local volume class. Handles the implementation of the local filesystem as a volume in
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.volumes
 * @since 3.0
 */
class Local extends FlysystemVolume implements LocalVolumeInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Local Folder');
    }

    // Properties
    // =========================================================================

    /**
     * Path to the root of this sources local folder.
     *
     * @var string|null
     */
    public $path;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->path !== null) {
            $this->path = str_replace('\\', '/', $this->path);
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['path'], 'required'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/volumes/Local/settings',
            [
                'volume' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getRootPath(): string
    {
        return FileHelper::normalizePath(Craft::parseEnv($this->path));
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        $parentDir = dirname($path);
        $newPath = ($parentDir && $parentDir !== '.' ? $parentDir . '/' : '') . $newName;

        try {
            if (!$this->filesystem()->rename($path, $newPath)) {
                throw new VolumeException('Couldn’t rename ' . $path);
            }
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException(Craft::t('app', 'Folder was not found while attempting to rename {path}!', ['path' => $path]));
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return LocalAdapter
     */
    protected function createAdapter(): LocalAdapter
    {
        return new LocalAdapter($this->getRootPath());
    }
}
