<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\errors\ExitException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\LegacyAssets\ContentWindowAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyScreenFragments;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Throwable;
use yii\base\Component;
use yii\base\ExitException as YiiExitException;
use yii\base\InvalidConfigException;
use yii\web\ResponseFormatterInterface;
use function CraftCms\Cms\pageTemplate;

/**
 * Template response formatter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TemplateResponseFormatter extends Component implements ResponseFormatterInterface
{
    public const FORMAT = 'template';

    /**
     * @inheritdoc
     * @throws InvalidConfigException if the response doesn’t have a TemplateResponseBehavior
     */
    public function format($response)
    {
        /** @var TemplateResponseBehavior|null $behavior */
        $behavior = $response->getBehavior(TemplateResponseBehavior::NAME);

        if (!$behavior) {
            throw new InvalidConfigException('TemplateResponseFormatter can only be used on responses with a TemplateResponseBehavior.');
        }

        $generalConfig = Cms::config();

        // If this is a preview request and `useIframeResizer` is enabled, register the iframe resizer script
        if (
            Craft::$app->getRequest()->getQueryParam('x-craft-live-preview') !== null &&
            $generalConfig->useIframeResizer
        ) {
            app(InternalAssetRegistry::class)->register(ContentWindowAsset::class);
        }

        $templateMode = $behavior->templateMode ? TemplateMode::from($behavior->templateMode) : null;

        /**
         * A Craft 5-era control panel screen is often just a template extending
         * `_layouts/cp`, with no `asCpScreen()` response to carry its parts, so
         * that layout hands its fragments over instead of drawing a document.
         * Collecting is the default; this converts in place rather than leaving
         * it to `RenderBridgedScreen`, because the response being formatted
         * here is a Yii one and has to carry the result out through Yii.
         *
         * A template that doesn't extend `_layouts/cp` — a site template, a
         * front-end preview — never reaches the collecting layout, so nothing
         * is collected and the rendered document stands exactly as it did.
         */
        $fragments = app(LegacyScreenFragments::class);

        // Render and return the template
        try {
            $response->content = pageTemplate($behavior->template, $behavior->variables, $templateMode);
        } catch (Throwable $e) {
            $fragments->reset();
            $previous = $e->getPrevious();
            if ($previous instanceof YiiExitException) {
                // Something called Craft::$app->end()
                if ($previous instanceof ExitException && $previous->output !== null) {
                    echo $previous->output;
                }
                return;
            }

            // Bail on the template response
            $response->format = Response::FORMAT_HTML;
            throw $e;
        }

        if (($collected = $fragments->fragments()) !== null) {
            $fragments->reset();
            BridgedScreen::send($response, $collected);

            return;
        }

        $fragments->reset();

        $headers = $response->getHeaders();

        if ($generalConfig->sendContentLengthHeader) {
            $headers->setDefault('content-length', (string)strlen($response->content));
        }

        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!$headers->has('content-type')) {
            $templateFile = Str::chopEnd(strtolower(app(TemplateResolver::class)->resolve($behavior->template)), '.twig');
            $mimeType = File::getMimeTypeByExtension($templateFile) ?? 'text/html';
            $headers->set('content-type', $mimeType . '; charset=' . $response->charset);
        }
    }
}
