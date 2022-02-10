<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\services\ElementSources;
use yii\web\Response;

/**
 * Class ElementSelectorModalsController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementSelectorModalsController extends BaseElementsController
{
    /**
     * Renders and returns the body of an ElementSelectorModal.
     *
     * @return Response
     */
    public function actionBody(): Response
    {
        $this->requireAcceptsJson();

        $sourceKeys = $providedSourceKeys = $this->request->getParam('sources');
        $elementType = $this->elementType();
        $context = $this->context();

        $showSiteMenu = $this->request->getParam('showSiteMenu', 'auto');

        if ($showSiteMenu !== 'auto') {
            $showSiteMenu = (bool)$showSiteMenu;
        }

        if (is_array($sourceKeys)) {
            $sourceKeys = array_flip($sourceKeys);
            $allSources = Craft::$app->getElementSources()->getSources($elementType);
            $sources = [];

            foreach ($allSources as $source) {
                if ($source['type'] === ElementSources::TYPE_HEADING) {
                    $sources[] = $source;
                } else if (isset($sourceKeys[$source['key']])) {
                    $sources[] = $source;
                    // Unset so we can keep track of which keys couldn't be found
                    unset($sourceKeys[$source['key']]);
                }
            }

            $sources = ElementSources::filterExtraHeadings($sources);

            // Did we miss any source keys? (This could happen if some are nested)
            if (!empty($sourceKeys)) {
                foreach (array_keys($sourceKeys) as $key) {
                    $source = ElementHelper::findSource($elementType, $key, $context);
                    if ($source !== null) {
                        $sources[] = $source;
                    }
                }

                // Sort it by the original order
                ArrayHelper::multisort($sources, 'key', [$providedSourceKeys]);
            }
        } else {
            $sources = Craft::$app->getElementSources()->getSources($elementType);
        }

        // Show the sidebar if there are at least two (non-heading) sources
        $showSidebar = (function() use ($sources): bool {
            $foundSource = false;
            foreach ($sources as $source) {
                if ($source['type'] !== ElementSources::TYPE_HEADING) {
                    if ($foundSource || !empty($source['nested'])) {
                        return true;
                    }
                    $foundSource = true;
                }
            }
            return false;
        })();

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('_elements/modalbody', [
                'context' => $context,
                'elementType' => $elementType,
                'sources' => $sources,
                'showSidebar' => $showSidebar,
                'showSiteMenu' => $showSiteMenu,
            ]),
        ]);
    }
}
