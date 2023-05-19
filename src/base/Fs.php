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
        ];
    }

    /**
     * Return value of $showHasUrlSetting
     *
     * @return bool
     */
    public function showHasUrlSetting(): bool
    {
        return $this->showHasUrlSetting;
    }

    /**
     * Return value of $showUrlSetting
     *
     * @return bool
     */
    public function showUrlSetting(): bool
    {
        return $this->showUrlSetting;
    }

    /**
     * @inheritdoc
     *
     * TODO: remove for Craft 5 and fully move $hasUrl and $url to FS settings
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();

        // url should keep being saved top-level, not inside settings
        // to be changed in Craft 5
        if (isset($settings['url']) && $this->url === $settings['url']) {
            unset($settings['url']);
        }

        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
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

        return $rules;
    }
}
