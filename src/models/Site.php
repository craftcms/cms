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
use craft\helpers\App;
use craft\i18n\Locale;
use craft\records\Site as SiteRecord;
use craft\validators\HandleValidator;
use craft\validators\LanguageValidator;
use craft\validators\UniqueValidator;
use craft\validators\UrlValidator;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Site model class.
 *
 * @property bool|string $enabled Enabled
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
    public ?int $id = null;

    /**
     * @var int|null Group ID
     */
    public ?int $groupId = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null Name
     */
    public ?string $language = null;

    /**
     * @var bool Primary site?
     */
    public bool $primary = false;

    /**
     * @var bool Has URLs
     */
    public bool $hasUrls = true;

    /**
     * @var int Sort order
     */
    public int $sortOrder = 1;

    /**
     * @var string|null Site UID
     */
    public ?string $uid = null;

    /**
     * @var DateTime|null Date created
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null Date updated
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var string|null Base URL
     * @see getBaseUrl()
     * @see setBaseUrl()
     */
    private ?string $_baseUrl = '@web/';

    /**
     * @var string|null Name
     * @see getName()
     * @see setName()
     */
    private ?string $_name = null;

    /**
     * @var bool|string Enabled
     * @see getEnabled()
     * @see setEnabled()
     */
    private bool|string $_enabled = true;

    /**
     * Returns the site’s name.
     *
     * @param bool $parse Whether to parse the name for an environment variable
     * @return string
     * @since 3.6.0
     */
    public function getName(bool $parse = true): string
    {
        return ($parse ? App::parseEnv($this->_name) : $this->_name) ?? '';
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
    public function getBaseUrl(bool $parse = true): ?string
    {
        if ($this->_baseUrl) {
            return $parse ? rtrim(App::parseEnv($this->_baseUrl), '/') . '/' : $this->_baseUrl;
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
     * Returns whether the site is enabled.
     *
     * @param bool $parse Whether to parse the name for an environment variable
     * @return bool|string
     * @since 4.0.0
     */
    public function getEnabled(bool $parse = true): bool|string
    {
        if ($this->primary) {
            return true;
        }

        if ($parse) {
            return App::parseBooleanEnv($this->_enabled) ?? true;
        }
        return $this->_enabled;
    }

    /**
     * Sets the site’s name.
     *
     * @param bool|string $name
     * @since 4.0.0
     */
    public function setEnabled(bool|string $name): void
    {
        $this->_enabled = $name;
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'name' => fn() => $this->getName(false),
                    'baseUrl' => fn() => $this->getBaseUrl(false),
                    'enabled' => fn() => $this->getEnabled(false),
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
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

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
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
        if (!isset($this->groupId)) {
            throw new InvalidConfigException('Site is missing its group ID');
        }

        if (($group = Craft::$app->getSites()->getGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid site group ID: ' . $this->groupId);
        }

        return $group;
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
     * Returns the site’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        return [
            'siteGroup' => $this->getGroup()->uid,
            'name' => $this->_name,
            'handle' => $this->handle,
            'language' => $this->language,
            'hasUrls' => $this->hasUrls,
            'baseUrl' => $this->_baseUrl ?: null,
            'sortOrder' => $this->sortOrder,
            'primary' => $this->primary,
            'enabled' => $this->getEnabled(false),
        ];
    }
}
