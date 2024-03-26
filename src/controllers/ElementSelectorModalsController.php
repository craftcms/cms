<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\helpers\Cp;
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

        return $this->asJson([
            'html' => Cp::elementIndexHtml($this->elementType(), [
                'context' => $this->context(),
                'class' => 'content',
                'sources' => $this->request->getParam('sources'),
                'showSiteMenu' => $this->request->getParam('showSiteMenu', 'auto'),
                'registerJs' => false,
            ]),
        ]);
    }
}
