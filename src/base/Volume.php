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
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

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
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title'
            ]
        ];

        // Require URLs for public Volumes.
        if ($this->hasUrls) {
            $rules[] = [['url'], 'required'];
        }

        return $rules;
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

        return rtrim(Craft::parseEnv($this->url), '/') . '/';
    }
}
