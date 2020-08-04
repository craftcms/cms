<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\records\Site as SiteRecord;
use craft\validators\HandleValidator;
use craft\validators\LanguageValidator;
use craft\validators\UniqueValidator;
use craft\validators\UrlValidator;
use yii\base\InvalidConfigException;

/**
 * Site model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Site extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Group ID
     */
    public $groupId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string|null Name
     */
    public $language;

    /**
     * @var bool Primary site?
     */
    public $primary = false;

    /**
     * @var bool Enabled?
     * @since 3.5.0
     */
    public $enabled = true;

    /**
     * @var bool Has URLs
     */
    public $hasUrls = true;

    /**
     * @var string|null Original name (set if [[name]] was overridden by the config)
     */
    public $originalName;

    /**
     * @var string|null Original base URL (set if [[baseUrl]] was overridden by the config)
     */
    public $originalBaseUrl;

    /**
     * @var string|null Base URL
     */
    public $baseUrl = '@web/';

    /**
     * @var int Sort order
     */
    public $sortOrder = 1;

    /**
     * @var string|null Site UID
     */
    public $uid;

    /**
     * @var \DateTime Date created
     */
    public $dateCreated;

    /**
     * @var \DateTime Date updated
     */
    public $dateUpdated;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init()
    {
        // Typecast DB values
        $this->id = (int)$this->id ?: null;
        $this->groupId = (int)$this->groupId ?: null;
        $this->primary = (bool)$this->primary;
        $this->enabled = (bool)$this->enabled;
        $this->hasUrls = (bool)$this->hasUrls;
        $this->sortOrder = (int)$this->sortOrder;

        parent::init();
    }

    /**
     * Returns the site’s base URL.
     *
     * @return string|null
     * @since 3.1.0
     */
    public function getBaseUrl()
    {
        if ($this->baseUrl) {
            return rtrim(Craft::parseEnv($this->baseUrl), '/') . '/';
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'baseUrl',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'baseUrl' => Craft::t('app', 'Base URL'),
            'handle' => Craft::t('app', 'Handle'),
            'language' => Craft::t('app', 'Language'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['groupId', 'name', 'handle', 'language'], 'required'];
        $rules[] = [['id', 'groupId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle', 'baseUrl'], 'string', 'max' => 255];
        $rules[] = [['language'], LanguageValidator::class, 'onlySiteLanguages' => false];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['baseUrl'], UrlValidator::class, 'defaultScheme' => 'http'];

        if (Craft::$app->getIsInstalled()) {
            $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => SiteRecord::class];
        }

        $rules[] = [
            ['enabled'], function(string $attribute) {
                if ($this->primary && !$this->enabled) {
                    $this->addError($attribute, Craft::t('app', 'The primary site cannot be disabled.'));
                }
            }
        ];

        return $rules;
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }

    /**
     * Returns the site's group
     *
     * @return SiteGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): SiteGroup
    {
        if ($this->groupId === null) {
            throw new InvalidConfigException('Site is missing its group ID');
        }

        if (($group = Craft::$app->getSites()->getGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid site group ID: ' . $this->groupId);
        }

        return $group;
    }

    /**
     * Overrides the name while keeping track of the original one.
     *
     * @param string $name
     */
    public function overrideName(string $name)
    {
        $this->originalName = (string)$this->name;
        $this->name = $name;
    }

    /**
     * Overrides the base URL while keeping track of the original one.
     *
     * @param string $baseUrl
     */
    public function overrideBaseUrl(string $baseUrl)
    {
        $this->originalBaseUrl = (string)$this->baseUrl;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Returns the field layout config for this site.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        return [
            'siteGroup' => $this->getGroup()->uid,
            'name' => $this->name,
            'handle' => $this->handle,
            'language' => $this->language,
            'hasUrls' => (bool)$this->hasUrls,
            'baseUrl' => $this->baseUrl ?: null,
            'sortOrder' => (int)$this->sortOrder,
            'primary' => (bool)$this->primary,
            'enabled' => (bool)$this->enabled,
        ];
    }
}
