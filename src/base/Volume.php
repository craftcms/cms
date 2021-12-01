<?php
declare(strict_types=1);
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
use craft\fs\Local;
use craft\helpers\Assets;
use craft\models\FieldLayout;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * Volume represents a volume created in a Craft installation.
 *
 * @mixin FieldLayoutBehavior
 */
class Volume extends SavableComponent implements VolumeInterface
{
    use VolumeTrait;

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
    public function getFieldLayout(): ?FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        if (!$this->hasUrls) {
            return null;
        }

        return rtrim(Craft::parseEnv($this->url), '/') . '/';
    }

    /**
     * Set the filesystem.
     *
     * @param FsInterface $fs
     */
    public function setFilesystem(FsInterface $fs): void {
        $this->_fs = $fs;
    }

    /**
     * Get the local file system.
     * @return FsInterface
     * @since 4.0.0
     */
    public function getFilesystem(): FsInterface {
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
