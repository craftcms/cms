<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\records\Site as SiteRecord;
use craft\app\validators\HandleValidator;
use craft\app\validators\UniqueValidator;
use craft\app\validators\UrlValidator;

/**
 * Site model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Site extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var string Name
     */
    public $language;

    /**
     * @var boolean Has URLs
     */
    public $hasUrls = true;

    /**
     * @var string Original base URL (set if [[baseUrl]] was overridden by the config)
     */
    public $originalBaseUrl;

    /**
     * @var string Base URL
     */
    public $baseUrl;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Normalize the base URL
        if (isset($config['baseUrl'])) {
            $config['baseUrl'] = rtrim($config['baseUrl'], '/').'/';
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['name', 'handle', 'language'], 'required'],
            [['id'], 'number', 'integerOnly' => true],
            [['name', 'handle', 'baseUrl'], 'string', 'max' => 255],
            [['language'], 'string', 'max' => 12],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['baseUrl'], UrlValidator::class, 'defaultScheme' => 'http'],
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
    public function __toString()
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Overrides the base URL while keeping track of the original one.
     *
     * @param string $baseUrl
     *
     * @return void
     */
    public function overrideBaseUrl($baseUrl)
    {
        $this->originalBaseUrl = (string) $this->baseUrl;
        $this->baseUrl = rtrim($baseUrl, '/').'/';
    }
}
