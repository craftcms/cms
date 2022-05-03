<?php
/**
 * The base class for all asset Volumes. All Volume types must extend this class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */

namespace craft\base;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Asset;
use craft\helpers\App;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\NotSupportedException;

/**
 * Volume is the base class for classes representing volumes in terms of objects.
 *
 * @mixin FieldLayoutBehavior
 */
abstract class Volume extends SavableComponent implements VolumeInterface
{
    use VolumeTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Asset::class,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'url' => Craft::t('app', 'URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => VolumeRecord::class];
        $rules[] = [['hasUrls'], 'boolean'];
        $rules[] = [['name', 'handle', 'url'], 'string', 'max' => 255];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'title',
                'uid',
            ],
        ];
        $rules[] = [['fieldLayout'], 'validateFieldLayout'];

        // Require URLs for public Volumes.
        if ($this->hasUrls) {
            $rules[] = [['url'], 'required'];
        }

        return $rules;
    }

    /**
     * Validates the field layout.
     *
     * @return void
     * @since 3.7.0
     */
    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->reservedFieldHandles = [
            'folder',
            'volume',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getFieldLayout()
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl()
    {
        if (!$this->hasUrls) {
            return false;
        }

        return rtrim(App::parseEnv($this->url), '/') . '/';
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path)
    {
        $this->createDir($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path)
    {
        $this->deleteDir($path);
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName)
    {
        $this->renameDir($path, $newName);
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        return $this->folderExists($path);
    }

    /**
     * Creates a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @deprecated in 3.6.0. Use [[createDirectory()]] instead.
     */
    public function createDir(string $path)
    {
        throw new NotSupportedException('createDir() has not been implemented.');
    }

    /**
     * Deletes a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @deprecated in 3.6.0. Use [[deleteDirectory()]] instead.
     */
    public function deleteDir(string $path)
    {
        throw new NotSupportedException('deleteDir() has not been implemented.');
    }

    /**
     * Renames a directory.
     *
     * @param string $path The path of the directory, relative to the source’s root
     * @param string $newName The new path of the directory, relative to the source’s root
     * @deprecated in 3.6.0. Use [[renameDirectory()]] instead.
     */
    public function renameDir(string $path, string $newName)
    {
        throw new NotSupportedException('renameDir() has not been implemented.');
    }

    /**
     * Returns whether a folder exists at the given path.
     *
     * @param string $path The folder path to check
     * @return bool
     * @deprecated in 3.7.0. Use [[directoryExists()]] instead.
     */
    public function folderExists(string $path): bool
    {
        throw new NotSupportedException('folderExists() has not been implemented.');
    }
}
