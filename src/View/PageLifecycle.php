<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\Twig\Events\PageStarting;
use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use Illuminate\Container\Attributes\Scoped;
use Throwable;

/**
 * Owns the page rendering lifecycle: output buffering, PageStarting/PageEnded
 * events, and placeholder replacement for head/body asset injection.
 *
 * Keeps TemplateManager focused on template rendering without coupling
 * it to asset output or page structure concerns.
 */
#[Scoped]
readonly class PageLifecycle
{
    public const string HEAD_PLACEHOLDER = '<![CDATA[CRAFT-BLOCK-HEAD]]>';

    public const string BODY_BEGIN_PLACEHOLDER = '<![CDATA[CRAFT-BLOCK-BODY-BEGIN]]>';

    public const string BODY_END_PLACEHOLDER = '<![CDATA[CRAFT-BLOCK-BODY-END]]>';

    public function __construct(
        private HtmlStack $HtmlStack,
    ) {}

    public function head(): void
    {
        echo self::HEAD_PLACEHOLDER;
    }

    public function beginBody(): void
    {
        echo self::BODY_BEGIN_PLACEHOLDER;
    }

    public function endBody(): void
    {
        echo self::BODY_END_PLACEHOLDER;
    }

    /**
     * Wraps a template render callback in the page lifecycle.
     *
     * Starts output buffering, fires PageStarting/PageEnded events, and replaces
     * the head/body placeholders in the buffered output with rendered assets.
     *
     * @param  callable(): string  $render  A callback that renders the template and returns the output.
     * @return string The final page HTML with placeholders replaced by asset HTML.
     */
    public function wrap(callable $render): string
    {
        ob_start();
        ob_implicit_flush(false);

        try {
            event(new PageStarting);

            echo $render();

            event($event = new PageEnded);

            return strtr((string) ob_get_clean(), [
                self::HEAD_PLACEHOLDER => $event->headHtml ?? $this->HtmlStack->headHtml(),
                self::BODY_BEGIN_PLACEHOLDER => $event->bodyBeginHtml ?? $this->HtmlStack->bodyBeginHtml(),
                self::BODY_END_PLACEHOLDER => $event->bodyEndHtml ?? $this->HtmlStack->bodyEndHtml(),
            ]);
        } catch (TemplateExitException) {
            // {% exit %} without a status code: return whatever has been
            // rendered so far as a normal 200 response.
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
    }
}
