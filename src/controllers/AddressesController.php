<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Address;
use craft\helpers\Cp;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

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
     * Renders an address’ card HTML.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionCardHtml(): Response
    {
        $this->requireAcceptsJson();

        $addressId = $this->request->getRequiredBodyParam('addressId');

        $address = Address::find()
            ->id($addressId)
            ->one();

        if (!$address) {
            throw new BadRequestHttpException("Invalid address ID: $addressId");
        }

        if (!$address->canView(Craft::$app->getUser()->getIdentity())) {
            throw new ForbiddenHttpException('User not authorized to view this address.');
        }

        $html = Cp::addressCardHtml($address, [
            'name' => $this->request->getBodyParam('name'),
        ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }

    /**
     * Returns address field info, based on a country code.
     *
     * @param string $countryCode
     * @return Response
     */
    public function actionFieldInfo(string $countryCode): Response
    {
        $addressesService = Craft::$app->getAddresses();

        $formatRepo = $addressesService->getAddressFormatRepository()->get($countryCode);
        $requiredFields = array_flip($formatRepo->getRequiredFields());
        $visibleFields = array_flip(array_merge(
                $formatRepo->getUsedFields(),
                $formatRepo->getUsedSubdivisionFields(),
            )) + $requiredFields;

        $administrativeAreaOptions = [];

        foreach ($addressesService->getSubdivisionRepository()->getList([$countryCode], Craft::$app->language) as $code => $label) {
            $administrativeAreaOptions[] = ['value' => $code, 'text' => $label];
        }

        $info = [
            'administrativeAreaOptions' => $administrativeAreaOptions,
        ];

        $attributes = [
            'addressLine1',
            'addressLine2',
            'postalCode',
            'sortingCode',
            'administrativeArea',
            'locality',
            'dependentLocality',
        ];

        foreach ($attributes as $attribute) {
            $info['fields'][$attribute] = [
                'label' => Address::addressAttributeLabel($attribute, $countryCode),
                'visible' => isset($visibleFields[$attribute]),
                'required' => isset($requiredFields[$attribute]),
            ];
        }

        return $this->asJson($info);
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
            'address',
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
