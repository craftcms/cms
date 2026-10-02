<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use Craft;
use craft\base\Model;
use CraftCms\Cms\Support\Facades\Deprecator;

/**
 * Base config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 */
class BaseConfig extends Model
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @var array Settings that have been renamed
     */
    protected static array $renamedSettings = [];

    /**
     * @var string|null The config filename
     */
    protected ?string $filename = null;

    /**
     * Factory method for creating new config objects.
     *
     * @param array $config
     * @return static
     */
    public static function create(array $config = []): static
    {
        // We can't use Craft::createObject() here because Craft may not be autoloadable yet
        return new static($config);
    }

    /**
     * @inheritdoc
     */
    final public function __construct($config = [])
    {
        if (class_exists(Craft::class, false) && Craft::$app) {
            $this->filename = Craft::$app->getConfig()->getLoadingConfigFile();
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset(static::$renamedSettings[$name])) {
            return $this->{static::$renamedSettings[$name]};
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (isset(static::$renamedSettings[$name])) {
            $newName = static::$renamedSettings[$name];

            if (class_exists(Craft::class, false)) {
                Deprecator::log(sprintf('%s::%s', static::class, $name), "`$name` has been renamed to `$newName`.", config_path($this->filename));
            }

            $this->$newName = $value;
            return;
        }

        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if (isset(static::$renamedSettings[$name])) {
            return isset($this->{static::$renamedSettings[$name]});
        }

        return parent::__isset($name);
    }

    /**
     * Restores the state of an object from an array. This
     * is used when the config is cached by Laravel.
     */
    public static function __set_state(array $stateData): static
    {
        $object = new static();

        foreach ($stateData as $prop => $state) {
            if (!property_exists($object, $prop)) {
                continue;
            }

            $object->{$prop} = $state;
        }

        return $object;
    }
}
