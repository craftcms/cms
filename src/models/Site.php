<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
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
 * @since 3.0
 */
class Site extends Model
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Normalize the base URL
        if (isset($config['baseUrl'])) {
            $config['baseUrl'] = rtrim($config['baseUrl'], '/') . '/';
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['groupId', 'name', 'handle', 'language'], 'required'],
            [['id', 'groupId'], 'number', 'integerOnly' => true],
            [['name', 'handle', 'baseUrl'], 'string', 'max' => 255],
            [['language'], LanguageValidator::class, 'onlySiteLanguages' => false],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['baseUrl'], UrlValidator::class, 'allowAlias' => true, 'defaultScheme' => 'http'],
        ];

        if (Craft::$app->getIsInstalled()) {
            $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => SiteRecord::class];
        }

        return $rules;
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name);
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
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }
}
