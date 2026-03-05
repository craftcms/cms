<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\errors\ExitException;
use craft\helpers\FileHelper;
use craft\web\assets\iframeresizer\ContentWindowAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
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
            Craft::$app->getView()->registerAssetBundle(ContentWindowAsset::class);
        }

        // Render and return the template
        try {
            $response->content = pageTemplate($behavior->template, $behavior->variables, $behavior->templateMode ? TemplateMode::from($behavior->templateMode) : null);
        } catch (Throwable $e) {
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

        $headers = $response->getHeaders();

        if ($generalConfig->sendContentLengthHeader) {
            $headers->setDefault('content-length', (string)strlen($response->content));
        }

        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!$headers->has('content-type')) {
            $templateFile = Str::chopEnd(strtolower(app(TemplateResolver::class)->resolve($behavior->template)), '.twig');
            $mimeType = FileHelper::getMimeTypeByExtension($templateFile) ?? 'text/html';
            $headers->set('content-type', $mimeType . '; charset=' . $response->charset);
        }
    }
}
