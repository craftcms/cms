<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\base\FsInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Asset;
use craft\fs\MissingFs;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * Volume model class.
 *
 * @mixin FieldLayoutBehavior
 * @property FsInterface $fs
 * @property string $fsHandle
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Volume extends Model
{
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
     * @var string The subpath to use in the transform filesystem
     */
    public string $transformSubpath = '';

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
    public function attributeLabels(): array
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
        $rules[] = [['name', 'handle'], 'required'];
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

        return $rules;
    }

    /**
     * Validates the field layout.
     */
    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->reservedFieldHandles = [
            'alt',
            'folder',
            'volume',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
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
            $handle = $this->getTransformFsHandle() ?? $this->getFsHandle();

            if ($handle === null) {
                throw new InvalidConfigException('Missing filesystem handle');
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
     * @param ?FsInterface $fs
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
            'transformFs' => $this->_transformFsHandle,
            'transformSubpath' => $this->transformSubpath,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'sortOrder' => $this->sortOrder,
        ];

        if (
            ($fieldLayout = $this->getFieldLayout()) &&
            ($fieldLayoutConfig = $fieldLayout->getConfig())
        ) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
