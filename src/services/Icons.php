<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RegisterIconSetsEvent;
use craft\icons\DirectoryIconSet;
use craft\icons\IconSet;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Icons service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getIcons()|`Craft::$app->getIcons()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class Icons extends Component
{
    /**
     * @event RegisterIconSetsEvent The event that is triggered when registering custom icon sets.
     *
     * ```php
     * use craft\events\RegisterIconSetsEvent;
     * use craft\services\Icons;
     * use yii\base\Event;
     *
     * Event::on(
     *     Icons::class,
     *     Icons::EVENT_REGISTER_ICON_SETS,
     *     function(RegisterIconSetsEvent $event) {
     *         $event->iconSets[] = new MyIconSet([
     *             'handle' => 'my-icons',
     *             'name' => 'My Icons',
     *         ]);
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_ICON_SETS = 'registerIconSets';

    /**
     * @var string The handle that refers to the built-in Font Awesome icons, when configuring which sets an icon picker should show.
     *
     * Font Awesome icons’ values aren’t prefixed with it.
     */
    public const FONT_AWESOME = 'fontawesome';

    /**
     * @var string The handle of the icon set registered via the <config5:customIconPath> config setting.
     */
    public const CUSTOM = 'custom';

    /**
     * @var array<string,IconSet>
     * @see getIconSets()
     */
    private array $_iconSets;

    /**
     * Parses an icon value into its set handle and icon name, if it references a custom icon set.
     *
     * @param string $value The icon value, e.g. `my-icons:arrow`
     * @return array{string,string}|null The set handle and icon name, or `null` if the value doesn’t reference a custom icon set
     */
    public static function parse(string $value): ?array
    {
        // Icon names can't contain slashes, so file paths (`C:\...`) won't match
        if (!preg_match('/^([a-zA-Z][\w\-]*):([^\/\\\\<>:]+)$/', $value, $match)) {
            return null;
        }
        return [$match[1], $match[2]];
    }

    /**
     * Returns all registered custom icon sets, indexed by their handles.
     *
     * @return array<string,IconSet>
     * @throws InvalidConfigException if an icon set has an invalid handle
     */
    public function getIconSets(): array
    {
        if (!isset($this->_iconSets)) {
            $event = new RegisterIconSetsEvent();

            $customIconPath = Craft::$app->getConfig()->getGeneral()->customIconPath;
            if ($customIconPath) {
                $event->iconSets[] = new DirectoryIconSet([
                    'handle' => self::CUSTOM,
                    'name' => Craft::t('app', 'Custom'),
                    'path' => $customIconPath,
                ]);
            }

            if ($this->hasEventHandlers(self::EVENT_REGISTER_ICON_SETS)) {
                $this->trigger(self::EVENT_REGISTER_ICON_SETS, $event);
            }

            $this->_iconSets = [];
            foreach ($event->iconSets as $iconSet) {
                if (
                    !preg_match('/^[a-zA-Z][\w\-]*$/', $iconSet->handle) ||
                    $iconSet->handle === self::FONT_AWESOME
                ) {
                    throw new InvalidConfigException("Invalid icon set handle: $iconSet->handle");
                }
                $this->_iconSets[$iconSet->handle] = $iconSet;
            }
        }

        return $this->_iconSets;
    }

    /**
     * Returns a custom icon set by its handle.
     *
     * @param string $handle
     * @return IconSet|null
     */
    public function getIconSetByHandle(string $handle): ?IconSet
    {
        return $this->getIconSets()[$handle] ?? null;
    }

    /**
     * Returns the names of all available icon sets, including Font Awesome, indexed by their handles.
     *
     * @return array<string,string>
     */
    public function getIconSetNames(): array
    {
        $names = [self::FONT_AWESOME => 'Font Awesome'];
        foreach ($this->getIconSets() as $handle => $iconSet) {
            $names[$handle] = $iconSet->name;
        }
        return $names;
    }
}
