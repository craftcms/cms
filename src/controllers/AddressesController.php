<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Address;
use craft\helpers\Address as AddressHelper;
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
    /**
     * @return string
     */
    public function actionAddAddress(): string
    {
        $namespace = Craft::$app->getRequest()->getParam('name', 'addresses');
        $addresses = Craft::$app->getRequest()->getParam($namespace, []);
        $addressesElements = [];
        foreach ($addresses as $key => $address) {
            $address = Address::create($address);
            $address->setFieldValuesFromRequest($namespace . '.fields');
            $addressesElements[$key] = $address;
        }
        $newAddress = Address::create([
            'countryCode' => 'US'
        ]);
        $addressesElements[] = $newAddress;
        return AddressHelper::addressCardsHtml($addressesElements, $namespace, true);
    }

    /**
     * @return \yii\web\Response|null
     */
    public function actionRenderAddressStandardFields(): ?Response
    {
        $view = Craft::$app->getView();
        $namespace = Craft::$app->getRequest()->getParam('name', 'address');
        $address = Craft::$app->getRequest()->getParam($namespace, []);
        unset($address['fields']); // Don't need this to render standard fields
        $address = Address::create($address);
        $html = $view->namespaceInputs(function() use ($address) {
            return Craft::$app->getView()->renderTemplate('_includes/forms/address-standard', [
                'address' => $address,
                'availableCountries' => null,
                'defaultCountryCode' => 'US',
                'hasErrors' => $address->hasErrors()
            ]);
        }, $namespace);

        return $this->asJson([
            'fieldHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * @return \yii\web\Response|null
     */
    public function actionRenderFormattedAddress(): ?Response
    {
        $view = Craft::$app->getView();
        $namespace = Craft::$app->getRequest()->getParam('name', 'address');
        $address = Craft::$app->getRequest()->getParam($namespace, []);
        unset($address['fields']); // Don't need this to render standard fields
        $address = Address::create($address);
        $html = Craft::$app->getAddresses()->formatAddress($address);
        return $this->asJson([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
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