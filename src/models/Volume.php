<?php
declare(strict_types = 1);
/**
 * The base class for all asset Volumes. All Volume types must extend this class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\base\FsInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Asset;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * Volume represents a volume created in a Craft installation.
 *
 * @mixin FieldLayoutBehavior
 *
 * @property-read null|\craft\models\FieldLayout $fieldLayout
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
     * @var string|null Filesystem handle
     */
    public ?string $filesystem = null;

    /**
     * @var string Title translation method
     * @since 3.6.0
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     * @since 3.6.0
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

    private ?FsInterface $_fs = null;

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
     *
     * @since 3.7.0
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
     * @since 3.5.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * Set the filesystem.
     *
     * @param FsInterface $fs
     */
    public function setFilesystem(FsInterface $fs): void
    {
        $this->_fs = $fs;
    }

    /**
     * Get the local file system.
     *
     * @return FsInterface
     * @since 4.0.0
     */
    public function getFilesystem(): FsInterface
    {
        if ($this->_fs) {
            return $this->_fs;
        }

        $fs = Craft::$app->getFilesystems()->getFilesystemByHandle($this->filesystem);

        if (!$fs) {
            throw new InvalidConfigException('No filesystem found by the handle ' . $this->filesystem);
        }

        return $this->_fs = $fs;
    }
}
