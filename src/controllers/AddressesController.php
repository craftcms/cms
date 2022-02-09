<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Address;
use craft\web\Controller;
use craft\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AddressController class is a controller that handles various address-related actions.
 * Note that all actions in the controller require an authenticated Craft session as well as the relevant permissions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressesController extends Controller
{
    public function actionGetInputHtml()
    {
        $view = \Craft::$app->getView();
        $name = $this->request->getRequiredBodyParam('name');
        $doValidate = (bool)$this->request->getBodyParam('doValidate', false);
        $id = $this->request->getRequiredBodyParam('id');
        $static = $this->request->getBodyParam('static', false);

        $address = new Address();
        $address->countryCode = 'US';
        $fieldLocationDot = str_replace(['[', ']'], ['.', ''], $name);
        $addressData = $this->request->getBodyParam($fieldLocationDot);
        $address->setAttributes($addressData, false);

        $format = \Craft::$app->getAddresses()->getAddressFormatRepository()->get($address->countryCode);

        if ($doValidate) {
            $address->validate();
        }

        $fieldHtml = $view->renderTemplate('_includes/forms/address', [
            'address' => $address,
            'id' => $id,
            'name' => $name,
            'addressFormat' => $format,
            'static' => $static,
        ]);

        $response = [
            'fieldHtml' => $fieldHtml,
            'success' => !$address->hasErrors(),
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml()
        ];

        return $this->asJson($response);
    }

    /**
     * Saves the address field layout.
     *
     * @return Response|null
     */
    public function actionSaveFieldLayout(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Address::class;
        $fieldLayout->reservedFieldHandles = [
            'address'
        ];

        if (!Craft::$app->getAddresses()->saveLayout($fieldLayout)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => [
                    'fieldLayout' => $fieldLayout,
                ],
            ]);
            $this->setFailFlash(Craft::t('app', 'Couldn’t save address fields.'));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Address fields saved.'));
        return $this->redirectToPostedUrl();
    }
}