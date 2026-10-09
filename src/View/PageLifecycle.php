<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\Twig\Events\PageStarting;
use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Container\Attributes\Scoped;
use Throwable;

/**
 * Owns the page rendering lifecycle: output buffering, PageStarting/PageEnded
 * events, and placeholder replacement for head/body asset injection.
 *
 * Keeps TemplateManager focused on template rendering without coupling
 * it to asset output or page structure concerns.
 *
 * @since 6.0.0
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

            $output = (string) ob_get_clean();

            /**
             * Fill a position this output has a placeholder for; hand the rest
             * back. A render with no placeholders at all is what a screen
             * collected for the control panel shell produces, and the document
             * that shell builds later in the request is what needs the assets.
             */
            $replacements = [];

            foreach ([
                [self::HEAD_PLACEHOLDER, Position::Head, $event->headHtml],
                [self::BODY_BEGIN_PLACEHOLDER, Position::BodyBegin, $event->bodyBeginHtml],
                [self::BODY_END_PLACEHOLDER, Position::BodyEnd, $event->bodyEndHtml],
            ] as [$placeholder, $position, $html]) {
                if (str_contains($output, $placeholder)) {
                    $replacements[$placeholder] = $html ?? $this->drain($position);

                    continue;
                }

                /**
                 * A listener may render a position's assets to decide what the
                 * event carries, which takes them off the stack before this
                 * runs. Leaving it at that loses them, so put them back.
                 */
                if ($html !== null && $html !== '') {
                    $this->HtmlStack->html($html, $position);
                }
            }

            return $replacements === [] ? $output : strtr($output, $replacements);
        } catch (TemplateExitException) {
            // {% exit %} without a status code: return whatever has been
            // rendered so far as a normal 200 response.
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
    }

    private function drain(Position $position): string
    {
        return match ($position) {
            Position::Head => $this->HtmlStack->headHtml(),
            Position::BodyBegin => $this->HtmlStack->bodyBeginHtml(),
            default => $this->HtmlStack->bodyEndHtml(),
        };
    }
}
