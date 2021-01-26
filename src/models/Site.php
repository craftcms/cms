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
use craft\i18n\Locale;
use craft\records\Site as SiteRecord;
use craft\validators\HandleValidator;
use craft\validators\LanguageValidator;
use craft\validators\UniqueValidator;
use craft\validators\UrlValidator;
use yii\base\InvalidConfigException;

/**
 * Site model class.
 *
 * @property string|null $baseUrl The site’s base URL
 * @property string $name The site’s name
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
     * @deprecated in 3.6.0
     */
    public $originalName;

    /**
     * @var string|null Original base URL (set if [[baseUrl]] was overridden by the config)
     * @deprecated in 3.6.0
     */
    public $originalBaseUrl;

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
     * @var string|null Base URL
     */
    private $_baseUrl = '@web/';

    /**
     * @var string|null Name
     */
    private $_name;

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
     * Returns the site’s name.
     *
     * @param bool $parse Whether to parse the name for an environment variable
     * @return string
     * @since 3.6.0
     */
    public function getName(bool $parse = true): string
    {
        return ($parse ? Craft::parseEnv($this->_name) : $this->_name) ?? '';
    }

    /**
     * Sets the site’s name.
     *
     * @param string $name
     * @since 3.6.0
     */
    public function setName(string $name): void
    {
        $this->_name = $name;
    }

    /**
     * Returns the site’s base URL.
     *
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string|null
     * @since 3.1.0
     */
    public function getBaseUrl(bool $parse = true)
    {
        if ($this->_baseUrl) {
            return $parse ? rtrim(Craft::parseEnv($this->_baseUrl), '/') . '/' : $this->_baseUrl;
        }

        return null;
    }

    /**
     * Sets the site’s base URL.
     *
     * @param string|null $baseUrl
     * @since 3.6.0
     */
    public function setBaseUrl(?string $baseUrl): void
    {
        $this->_baseUrl = $baseUrl;
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
                'name' => function() {
                    return $this->getName(false);
                },
                'baseUrl' => function() {
                    return $this->getBaseUrl(false);
                },
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
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'name';
        $attributes[] = 'baseUrl';
        return $attributes;
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->getName()) ?: static::class;
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
     * @deprecated in 3.6.0
     */
    public function overrideName(string $name)
    {
        $this->originalName = (string)$this->_name;
        $this->setName($name);
    }

    /**
     * Overrides the base URL while keeping track of the original one.
     *
     * @param string $baseUrl
     * @deprecated in 3.6.0
     */
    public function overrideBaseUrl(string $baseUrl)
    {
        $this->originalBaseUrl = (string)$this->_baseUrl;
        $this->setBaseUrl($baseUrl);
    }

    /**
     * Returns the locale for this site’s language.
     *
     * @return Locale
     * @since 3.5.8
     */
    public function getLocale(): Locale
    {
        if ($this->language === Craft::$app->language) {
            return Craft::$app->getLocale();
        }
        return new Locale($this->language);
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
            'name' => $this->originalName ?? $this->_name,
            'handle' => $this->handle,
            'language' => $this->language,
            'hasUrls' => (bool)$this->hasUrls,
            'baseUrl' => $this->originalBaseUrl ?? ($this->_baseUrl ?: null),
            'sortOrder' => (int)$this->sortOrder,
            'primary' => (bool)$this->primary,
            'enabled' => (bool)$this->enabled,
        ];
    }
}
