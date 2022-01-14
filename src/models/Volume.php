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
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * Volume model class.
 *
 * @mixin FieldLayoutBehavior
 * @property-read null|\craft\models\FieldLayout $fieldLayout
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
     * @var string The filesystem handle, or an environment variable that references it
     */
    public string $fs;

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
     */
    public function getFs(): FsInterface
    {
        if ($this->_fs) {
            return $this->_fs;
        }

        $fs = Craft::$app->getFs()->getFilesystemByHandle($this->fs);

        if (!$fs) {
            throw new InvalidConfigException('No filesystem found by the handle ' . $this->fs);
        }

        return $this->_fs = $fs;
    }

    /**
     * Set the filesystem.
     *
     * @param FsInterface $fs
     */
    public function setFs(FsInterface $fs): void
    {
        $this->_fs = $fs;
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
            'fs' => $this->fs,
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
