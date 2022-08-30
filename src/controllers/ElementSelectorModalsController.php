<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
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

        $sourceKeys = $this->request->getParam('sources');
        $elementType = $this->elementType();
        $context = $this->context();

        $showSiteMenu = $this->request->getParam('showSiteMenu', 'auto');

        if ($showSiteMenu !== 'auto') {
            $showSiteMenu = (bool)$showSiteMenu;
        }

        if (is_array($sourceKeys)) {
            $indexedSourceKeys = array_flip($sourceKeys);
            $allSources = Craft::$app->getElementSources()->getSources($elementType);
            $sources = [];

            foreach ($allSources as $source) {
                if ($source['type'] === ElementSources::TYPE_HEADING) {
                    $sources[] = $source;
                } elseif (isset($indexedSourceKeys[$source['key']])) {
                    $sources[] = $source;
                    // Unset so we can keep track of which keys couldn't be found
                    unset($indexedSourceKeys[$source['key']]);
                }
            }

            $sources = ElementSources::filterExtraHeadings($sources);

            // Did we miss any source keys? (This could happen if some are nested)
            if (!empty($indexedSourceKeys)) {
                foreach (array_keys($indexedSourceKeys) as $key) {
                    $source = ElementHelper::findSource($elementType, $key, $context);
                    if ($source !== null) {
                        // If it was listed after another source key that made it in, insert it there
                        $pos = array_search($key, $sourceKeys);
                        $inserted = false;
                        if ($pos > 0) {
                            $prevKey = $sourceKeys[$pos - 1];
                            foreach ($sources as $i => $otherSource) {
                                if (($otherSource['key'] ?? null) === $prevKey) {
                                    array_splice($sources, $i + 1, 0, [$source]);
                                    $inserted = true;
                                    break;
                                }
                            }
                        }
                        if (!$inserted) {
                            $sources[] = $source;
                        }
                    }
                }
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
            'html' => $this->getView()->renderTemplate('_elements/modalbody.twig', [
                'context' => $context,
                'elementType' => $elementType,
                'sources' => $sources,
                'showSidebar' => $showSidebar,
                'showSiteMenu' => $showSiteMenu,
            ]),
        ]);
    }
}
