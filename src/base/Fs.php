<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\helpers\App;
use craft\validators\HandleValidator;

/**
 * Field is the base class for classes representing filesystems in terms of objects.
 *
 * @property-read null|string $rootUrl
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class Fs extends SavableComponent implements FsInterface
{
    use FsTrait;

    public const CONFIG_MIMETYPE = 'mimetype';
    public const CONFIG_VISIBILITY = 'visibility';

    public const VISIBILITY_DEFAULT = 'default';
    public const VISIBILITY_HIDDEN = 'hidden';
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        if (!$this->hasUrls) {
            return null;
        }

        $url = App::parseEnv($this->url);
        if (is_string($url)) {
            $url = rtrim($url, '/');
        }

        return $url ? "$url/" : null;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'url' => Craft::t('app', 'Base URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getShowHasUrlSetting(): bool
    {
        return static::$showHasUrlSetting;
    }

    /**
     * @inheritdoc
     */
    public function getShowUrlSetting(): bool
    {
        return static::$showUrlSetting;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [
            'url',
            'required',
            'when' => fn(self $fs) => $fs->hasUrls && $this->getShowUrlSetting(),
        ];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'new',
                'title',
                'uid',
            ],
        ];

        return $rules;
    }
}
