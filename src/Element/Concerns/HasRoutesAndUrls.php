<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\NestedElementInterface;
use craft\events\DefineUrlEvent;
use craft\events\SetElementRouteEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\twig\AllowedInSandbox;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Html;
use Twig\Markup;

/**
 * HasRoutesAndUrls provides URL generation and routing capabilities for elements.
 *
 * This trait contains methods for determining an element's URI format, generating URLs,
 * resolving routes when an element's URL is requested, and creating HTML links. It also
 * defines events that allow customization of URL generation and route resolution.
 *
 * @property string|null $cpEditUrl The element’s edit URL in the control panel
 * @property string|null $uriFormat The URI format used to generate this element’s URL
 * @property string|null $url The element’s full URL
 * @property mixed $route The route that should be used when the element’s URI is requested
 * @property Markup|null $link An anchor pre-filled with this element’s URL and title
 *
 * @internal
 */
trait HasRoutesAndUrls
{
    /**
     * @event SetElementRouteEvent The event that is triggered when defining the route that should be used when this element’s URL is requested.
     *
     * Set [[Event::$handled]] to `true` to explicitly tell the element that a route has been set (even if you’re
     * setting it to `null`).
     *
     * ```php
     * Event::on(craft\elements\Entry::class, craft\base\Element::EVENT_SET_ROUTE, function(craft\events\SetElementRouteEvent $e) {
     *     // @var craft\elements\Entry $entry
     *     $entry = $e->sender;
     *
     *     if ($entry->uri === 'pricing') {
     *         $e->route = 'module/pricing/index';
     *
     *         // Explicitly tell the element that a route has been set,
     *         // and prevent other event handlers from running, and tell
     *         $e->handled = true;
     *     }
     * });
     * ```
     */
    public const string EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event DefineUrlEvent The event that is triggered before defining the element’s URL.
     *
     * It can be used to provide a custom URL, completely bypassing the default URL generation.
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
     * use craft\events\DefineUrlEvent;
     * use craft\helpers\UrlHelper;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_BEFORE_DEFINE_URL,
     *     function(DefineUrlEvent $e
     * ) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     $event->url = '...';
     * });
     * ```
     *
     * To prevent the element from getting a URL, ensure `$event->url` is set to `null`,
     * and set `$event->handled` to `true`.
     *
     * Note that [[EVENT_DEFINE_URL]] will still be called regardless of what happens with this event.
     *
     * @see getUrl()
     * @since 4.4.6
     */
    public const string EVENT_BEFORE_DEFINE_URL = 'beforeDefineUrl';

    /**
     * @event DefineUrlEvent The event that is triggered when defining the element’s URL.
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
     * use craft\events\DefineUrlEvent;
     * use craft\helpers\UrlHelper;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_DEFINE_URL,
     *     function(DefineUrlEvent $e
     * ) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     // Add a custom query string param to the URL
     *     if ($event->value !== null) {
     *         $event->url = UrlHelper::urlWithParams($event->url, [
     *             'foo' => 'bar',
     *         ]);
     *     }
     * });
     * ```
     *
     * To prevent the element from getting a URL, ensure `$event->url` is set to `null`,
     * and set `$event->handled` to `true`.
     *
     * @see getUrl()
     * @since 4.3.0
     */
    public const string EVENT_DEFINE_URL = 'defineUrl';

    /**
     * @var string|null The element’s URI
     */
    #[AllowedInSandbox]
    public ?string $uri = null;

    /**
     * {@inheritdoc}
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUriFormat(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): mixed
    {
        // Fire a 'setRoute' event
        if ($this->hasEventHandlers(self::EVENT_SET_ROUTE)) {
            $event = new SetElementRouteEvent;
            $this->trigger(self::EVENT_SET_ROUTE, $event);
            if ($event->handled || $event->route !== null) {
                return $event->route ?: null;
            }
        }

        if ($this instanceof NestedElementInterface) {
            $field = $this->getField();
            if ($field) {
                return $field->getRouteForElement($this);
            }
        }

        return $this->route();
    }

    /**
     * Returns the route that should be used when the element's URI is requested.
     *
     * @return string|array|null The route that the request should use, or null if no special action should be taken
     *
     * @see getRoute()
     */
    protected function route(): array|string|null
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsHomepage(): bool
    {
        return $this->uri === Element::HOMEPAGE_URI;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): ?string
    {
        $url = null;
        $handled = false;

        // Fire a 'beforeDefineUrl' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DEFINE_URL)) {
            $event = new DefineUrlEvent;
            $this->trigger(self::EVENT_BEFORE_DEFINE_URL, $event);
            $url = $event->url;
            $handled = $event->handled;
        }

        // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
        if ($url === null && ! $handled && isset($this->uri)) {
            $path = $this->getIsHomepage() ? '' : $this->uri;
            $url = UrlHelper::siteUrl($path, null, null, $this->siteId);
        }

        // Fire a 'defineUrl' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_URL)) {
            $event = new DefineUrlEvent(['url' => $url]);
            $this->trigger(self::EVENT_DEFINE_URL, $event);
            // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
            if ($event->url !== null || $event->handled) {
                $url = $event->url;
            }
        }

        if ($url === null) {
            return null;
        }

        return Html::encodeSpaces($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl(): ?string
    {
        if (! $this->id) {
            return null;
        }

        $url = $this->cpEditUrl();

        return $url ? ElementHelper::addElementEditorUrlParams($url, $this) : null;
    }

    /**
     * Returns the element's edit URL in the control panel.
     *
     * @since 3.7.0
     */
    protected function cpEditUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPostEditUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLink(): ?Markup
    {
        if (($url = $this->getUrl()) === null) {
            return null;
        }

        $a = Html::a(Html::encode($this->getUiLabel()), $url);

        return Template::raw($a);
    }
}
