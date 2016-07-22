<?php
namespace craft\app\volumes;

use Craft;
use craft\app\base\Volume;
use craft\app\errors\VolumeObjectExistsException;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\helpers\Io;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;

/**
 * The local volume class. Handles the implementation of the local filesystem as a volume in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.volumes
 * @since      3.0
 */
class Local extends Volume
{
    // Static
    // =========================================================================

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
    public static function displayName()
    {
        return Craft::t('app', 'Local Folder');
    }

    /**
     * @inheritdoc
     */
    public static function isLocal()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        if (isset($config['path'])) {
            $config['path'] = rtrim($config['path'], '/');
        }

        parent::populateModel($model, $config);
    }

    // Properties
    // =========================================================================

    /**
     * Path to the root of this sources local folder.
     *
     * @var string
     */
    public $path = "";

    // Public Methods
    // =========================================================================

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
    public function getRootPath()
    {
        return Craft::$app->getConfig()->parseEnvironmentString($this->path);
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl()
    {
        return rtrim(Craft::$app->getConfig()->parseEnvironmentString($this->url), '/').'/';
    }

    /**
     * @inheritdoc
     */
    public function renameDir($path, $newName)
    {
        $newPath = Io::getParentFolderPath($path).$newName;

        try {
            return $this->getFilesystem()->rename($path, $newPath);
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
     *
     * @return LocalAdapter
     */
    protected function createAdapter()
    {
        return new LocalAdapter($this->getRootPath());
    }
}
