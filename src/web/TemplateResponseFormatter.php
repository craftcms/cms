<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\web\assets\iframeresizer\ContentWindowAsset;
use Throwable;
use yii\base\Component;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\ResponseFormatterInterface;

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

        $view = Craft::$app->getView();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        // If this is a preview request and `useIframeResizer` is enabled, register the iframe resizer script
        if (
            Craft::$app->getRequest()->getQueryParam('x-craft-live-preview') !== null &&
            $generalConfig->useIframeResizer
        ) {
            $view->registerAssetBundle(ContentWindowAsset::class);
        }

        // Render and return the template
        try {
            $response->content = $view->renderPageTemplate($behavior->template, $behavior->variables, $behavior->templateMode);
        } catch (Throwable $e) {
            if (!$e->getPrevious() instanceof ExitException) {
                // Bail on the template response
                $response->format = Response::FORMAT_HTML;
                throw $e;
            }

            // Something called Craft::$app->end()
            return;
        }

        $headers = $response->getHeaders();

        if ($generalConfig->sendContentLengthHeader) {
            $headers->setDefault('content-length', (string)strlen($response->content));
        }

        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!$headers->has('content-type')) {
            $templateFile = StringHelper::removeRight(strtolower($view->resolveTemplate($behavior->template)), '.twig');
            $mimeType = FileHelper::getMimeTypeByExtension($templateFile) ?? 'text/html';
            $headers->set('content-type', $mimeType . '; charset=' . $response->charset);
        }
    }
}
