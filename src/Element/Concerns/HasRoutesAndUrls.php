<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\BeforeDefineUrl;
use CraftCms\Cms\Element\Events\DefineUrl;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Support\HtmlString;
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
     * @var string|null The element's URI
     */
    #[AllowedInSandbox]
    public ?string $uri = null;

    public static function hasUris(): bool
    {
        return false;
    }

    public function getUriFormat(): ?string
    {
        return null;
    }

    public function getRoute(): mixed
    {
        event($event = new SetRoute($this));

        if ($event->handled || $event->route !== null) {
            return $event->route ?: null;
        }

        if ($this instanceof NestedElementInterface && $field = $this->getField()) {
            return $field->getRouteForElement($this);
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

    public function getIsHomepage(): bool
    {
        return $this->uri === Element::HOMEPAGE_URI;
    }

    public function getUrl(): ?string
    {
        event($beforeEvent = new BeforeDefineUrl($this));

        $url = $beforeEvent->url;
        $handled = $beforeEvent->handled;

        // If BeforeDefineUrl::$url is set to null, only respect that if $handled is true
        if ($url === null && ! $handled && isset($this->uri)) {
            $path = $this->getIsHomepage() ? '' : $this->uri;
            $url = Url::siteUrl($path, null, null, $this->siteId);
        }

        event($event = new DefineUrl($this, $url));

        // If DefineUrl::$url is set to null, only respect that if $handled is true
        if ($event->url !== null || $event->handled) {
            $url = $event->url;
        }

        if ($url === null) {
            return null;
        }

        return Html::encodeSpaces($url);
    }

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
     */
    protected function cpEditUrl(): ?string
    {
        return null;
    }

    public function getPostEditUrl(): ?string
    {
        return null;
    }

    public function getLink(): ?HtmlString
    {
        if (($url = $this->getUrl()) === null) {
            return null;
        }

        $a = Html::a(Html::encode($this->getUiLabel()), $url);

        return new HtmlString($a);
    }
}
