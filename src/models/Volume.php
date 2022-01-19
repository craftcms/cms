<?php
declare(strict_types = 1);
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
     * @var FsInterface
     * @see getFs()
     * @see setFs()
     */
    private FsInterface $_fs;

    /**
     * @var string|null
     * @see getFsHandle()
     * @see setFsHandle()
     */
    private ?string $_fsHandle = null;

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

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
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
    public function attributeLabels(): array
    {
        return [
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
                throw new InvalidConfigException("Invalid filesystem handle: $this->_fsHandle");
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
