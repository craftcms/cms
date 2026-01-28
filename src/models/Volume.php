<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\BaseFsInterface;
use craft\base\Chippable;
use craft\base\CpEditable;
use craft\base\Field;
use craft\base\FieldLayoutProviderInterface;
use craft\base\FsInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Asset;
use craft\fs\MissingFs;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use Generator;
use yii\base\InvalidConfigException;

/**
 * Volume model class.
 *
 * @mixin FieldLayoutBehavior
 * @property FsInterface $fs
 * @property string $fsHandle
 * @property string $subpath
 * @property string $transformSubpath
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Volume extends Model implements
    BaseFsInterface,
    Chippable,
    CpEditable,
    FieldLayoutProviderInterface
{
    /**
     * @inheritdoc
     */
    public static function get(string|int $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Craft::$app->getVolumes()->getVolumeById($id);
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string Title translation method
     * @phpstan-var Field::TRANSLATION_METHOD_NONE|Field::TRANSLATION_METHOD_SITE|Field::TRANSLATION_METHOD_SITE_GROUP|Field::TRANSLATION_METHOD_LANGUAGE|Field::TRANSLATION_METHOD_CUSTOM
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     */
    public ?string $titleTranslationKeyFormat = null;

    /**
     * @var string Alternative text translation method
     * @since 5.0.0
     */
    public string $altTranslationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var null|string Alternative text translation key format
     * @since 5.0.0
     */
    public ?string $altTranslationKeyFormat = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @var string The subpath to use in the filesystem for uploading files to this volume
     * @see getSubpath()
     * @see setSubpath()
     */
    private string $_subpath = '';

    /**
     * @var string The subpath to use in the transform filesystem
     * @see getTransformSubpath()
     * @see setTransformSubpath()
     */
    private string $_transformSubpath = '';

    /**
     * @var FsInterface|null
     * @see getFs()
     * @see setFs()
     */
    private ?FsInterface $_fs = null;

    /**
     * @var string|null
     * @see getFsHandle()
     * @see setFsHandle()
     */
    private ?string $_fsHandle = null;

    /**
     * @var FsInterface|null
     * @see getTransformFs()
     * @see setTransformFs()
     */
    private ?FsInterface $_transformFs = null;

    /**
     * @var string|null
     * @see getTransformFsHandle()
     * @see setTransformFsHandle()
     */
    private ?string $_transformFsHandle = null;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (isset($config['fs']) && is_string($config['fs'])) {
            $config['fsHandle'] = ArrayHelper::remove($config, 'fs');
        }

        if (isset($config['transformFs']) && is_string($config['transformFs'])) {
            $config['transformFsHandle'] = ArrayHelper::remove($config, 'transformFs');
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Asset::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/assets/volumes/$this->id");
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'subpath';
        $attributes[] = 'transformSubpath';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'url' => Craft::t('app', 'URL'),
            'fsHandle' => Craft::t('app', 'Asset Filesystem'),
            'subpath' => Craft::t('app', 'Subpath'),
            'transformFsHandle' => Craft::t('app', 'Transform Filesystem'),
            'transformSubpath' => Craft::t('app', 'Transform Subpath'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'trim'];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => VolumeRecord::class];
        $rules[] = [['name', 'handle', 'fsHandle'], 'required'];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'temp',
                'title',
                'uid',
            ],
        ];
        $rules[] = [['fieldLayout'], 'validateFieldLayout'];
        $rules[] = [['subpath'], fn($attribute) => $this->validateUniqueSubpath($attribute), 'skipOnEmpty' => false];

        $tempAssetUploadFs = App::parseEnv(Craft::$app->getConfig()->getGeneral()->tempAssetUploadFs);
        if ($tempAssetUploadFs) {
            $rules[] = [
                ['fsHandle'],
                'compare',
                'compareAttribute' => 'fsHandle',
                'compareValue' => $tempAssetUploadFs,
                'operator' => '!=',
                'message' => Craft::t('app', 'This filesystem has been reserved for temporary asset uploads. Please choose a different one for your volume.'),
            ];
            $rules[] = [
                ['transformFsHandle'],
                'compare',
                'compareAttribute' => 'transformFsHandle',
                'compareValue' => $tempAssetUploadFs,
                'operator' => '!=',
                'message' => Craft::t('app', 'This filesystem has been reserved for temporary asset uploads. Please choose a different one for your volume.'),
            ];
        }

        return $rules;
    }

    /**
     * Validate a unique subpath - not just the entire subpath, but even just the first subfolder
     *
     * e.g. if Volume A uses $MY_FS and its subpath is set to foo/bar,
     * and Volume B wishes to also use $MY_FS
     * and its subpath is either empty, or set to foo, foo/bar, or foo/bar/baz,
     * it should result in a validation error due to the conflict with Volume A
     */
    private function validateUniqueSubpath(string $attribute): void
    {
        // get all volumes that use the same FS, excluding current volume
        $query = VolumeRecord::find()
            ->andWhere(['fs' => $this->_fsHandle])
            ->asArray();

        if ($this->id !== null) {
            $query->andWhere('id != ' . $this->id);
        }

        $records = $query->all();

        // if there are other volumes using the same FS
        // and this volume wants to have an empty subpath - add error
        if (!empty($records) && empty($this->$attribute)) {
            $this->addError($attribute, Craft::t('app', 'A subpath is required for this filesystem.'));
        }

        // make sure subpath starts with a unique dir across all volumes that use this FS
        foreach ($records as $record) {
            if (strcmp(explode('/', $record[$attribute])[0], explode('/', $this->$attribute)[0]) === 0) {
                $this->addError($attribute, Craft::t('app', 'The subpath cannot overlap with any other volumes sharing the same filesystem.'));
            }
        }
    }

    /**
     * Validates the field layout.
     */
    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->reservedFieldHandles = [
            'alt',
            'extension',
            'filename',
            'folder',
            'height',
            'kind',
            'size',
            'volume',
            'width',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * Returns the volume’s filesystem.
     *
     * @return FsInterface
     * @throws InvalidConfigException if [[fsHandle]] is missing or invalid
     */
    public function getFs(): FsInterface
    {
        if (!isset($this->_fs)) {
            $handle = $this->getFsHandle();
            if (!$handle) {
                throw new InvalidConfigException('Volume is missing its filesystem handle.');
            }
            $fs = Craft::$app->getFs()->getFilesystemByHandle($handle);
            if (!$fs) {
                Craft::error("Invalid filesystem handle: $this->_fsHandle for the $this->name volume.");
                return new MissingFs(['handle' => $this->_fsHandle]);
            }
            $this->_fs = $fs;
        }

        return $this->_fs;
    }

    /**
     * Set the filesystem.
     *
     * @param FsInterface $fs
     */
    public function setFs(FsInterface $fs): void
    {
        $this->_fs = $fs;
        $this->_fsHandle = $fs->handle;
    }

    /**
     * Returns the filesystem handle.
     *
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string|null
     */
    public function getFsHandle(bool $parse = true): ?string
    {
        if ($this->_fsHandle) {
            return $parse ? App::parseEnv($this->_fsHandle) : $this->_fsHandle;
        }
        return null;
    }

    /**
     * Sets the filesystem handle.
     *
     * @param string $handle
     */
    public function setFsHandle(string $handle): void
    {
        $this->_fsHandle = $handle;
        $this->_fs = null;
    }


    /**
     * Returns the volume’s transform filesystem.
     *
     * @return FsInterface
     * @throws InvalidConfigException if [[fsHandle]] is missing or invalid
     */
    public function getTransformFs(): FsInterface
    {
        if (!isset($this->_transformFs)) {
            $handle = $this->getTransformFsHandle();
            if (!$handle) {
                return $this->getFs();
            }
            $fs = Craft::$app->getFs()->getFilesystemByHandle($handle);
            if (!$fs) {
                throw new InvalidConfigException("Invalid filesystem handle: $handle");
            }
            $this->_transformFs = $fs;
        }

        return $this->_transformFs;
    }

    /**
     * Set the transform filesystem.
     *
     * @param FsInterface|null $fs
     */
    public function setTransformFs(?FsInterface $fs): void
    {
        if ($fs) {
            $this->_transformFs = $fs;
            $this->_transformFsHandle = $fs->handle;
        } else {
            $this->_transformFsHandle = $this->_transformFs = null;
        }
    }

    /**
     * Returns the transform filesystem handle. If none set, will return the current fs handle.
     *
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string|null
     */
    public function getTransformFsHandle(bool $parse = true): ?string
    {
        if ($this->_transformFsHandle) {
            return $parse ? App::parseEnv($this->_transformFsHandle) : $this->_transformFsHandle;
        }
        return null;
    }

    /**
     * Sets the transform filesystem handle.
     *
     * @param string|null $handle
     */
    public function setTransformFsHandle(?string $handle): void
    {
        $this->_transformFsHandle = $handle;
        $this->_transformFs = null;
    }

    /**
     * Returns the volume’s config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'fs' => $this->_fsHandle,
            'subpath' => $this->_subpath,
            'transformFs' => $this->_transformFsHandle,
            'transformSubpath' => $this->_transformSubpath,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'altTranslationMethod' => $this->altTranslationMethod,
            'altTranslationKeyFormat' => $this->altTranslationKeyFormat ?: null,
            'sortOrder' => $this->sortOrder,
        ];

        $fieldLayout = $this->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();
        if ($fieldLayoutConfig) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        $rootUrl = $this->getFs()->getRootUrl() ?? '';
        return ($rootUrl !== '' ? StringHelper::ensureRight($rootUrl, '/') : '') . $this->getSubpath();
    }

    /**
     * Returns the volume’s subpath.
     *
     * @param bool $ensureTrailing Whether to include a trailing slash
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string
     * @since 5.0.0
     */
    public function getSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? App::parseEnv($this->_subpath) : $this->_subpath;

        if ($ensureTrailing && $subpath !== '' && !str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    /**
     * Sets the volume’s subpath, ensuring it's a string.
     *
     * @param string|null $subpath
     */
    public function setSubpath(?string $subpath): void
    {
        $this->_subpath = $subpath ?? '';
    }

    /**
     * Returns the volume’s transform subpath.
     *
     * @param bool $ensureTrailing Whether to include a trailing slash
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string
     * @since 5.2.0
     */
    public function getTransformSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? App::parseEnv($this->_transformSubpath) : $this->_transformSubpath;

        if ($ensureTrailing && $subpath !== '' && !str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    /**
     * Sets the volume’s transform subpath, ensuring it's a string.
     *
     * @param string|null $subpath
     * @since 5.2.0
     */
    public function setTransformSubpath(?string $subpath): void
    {
        $this->_transformSubpath = $subpath ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        return $this->getFs()->getFileList($this->getSubpath() . $directory, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        return $this->getFs()->getFileSize($this->getSubpath() . $uri);
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        return $this->getFs()->getDateModified($this->getSubpath() . $uri);
    }


    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->getFs()->write($this->getSubpath() . $path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        return $this->getFs()->read($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->getFs()->writeFileFromStream($this->getSubpath() . $path, $stream, $config);
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return $this->getFs()->fileExists($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        $this->getFs()->deleteFile($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $subpath = $this->getSubpath();
        $this->getFs()->renameFile($subpath . $path, $subpath . $newPath);
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $subpath = $this->getSubpath();
        $this->getFs()->copyFile($subpath . $path, $subpath . $newPath);
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        return $this->getFs()->getFileStream($this->getSubpath() . $uriPath);
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        return $this->getFs()->directoryExists($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        $this->getFs()->createDirectory($this->getSubpath() . $path, $config);
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        $this->getFs()->deleteDirectory($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        $this->getFs()->renameDirectory($this->getSubpath() . $path, $newName);
    }
}
