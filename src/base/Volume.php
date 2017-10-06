<?php
/**
 * The base class for all asset Volumes.  All Volume types must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 */

namespace craft\base;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

/**
 * Volume is the base class for classes representing volumes in terms of objects.
 *
 * @mixin FieldLayoutBehavior
 */
abstract class Volume extends SavableComponent implements VolumeInterface
{
    // Traits
    // =========================================================================

    use VolumeTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Asset::class
            ],
        ];
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
    public function rules()
    {
        $rules = [
            [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => VolumeRecord::class],
            [['hasUrls'], 'boolean'],
            [['name', 'handle', 'url'], 'string', 'max' => 255],
            [['name', 'handle'], 'required'],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'id',
                    'dateCreated',
                    'dateUpdated',
                    'uid',
                    'title'
                ]
            ],
        ];

        // Require URLs for public Volumes.
        if ($this->hasUrls) {
            $rules[] = [['url'], 'required'];
        }

        return $rules;
    }
}
