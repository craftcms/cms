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

        /** @var Address|null $address */
        $address = Address::find()
            ->id($addressId)
            ->one();

        if (!$address) {
            throw new BadRequestHttpException("Invalid address ID: $addressId");
        }

        if (!Craft::$app->getElements()->canView($address)) {
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
     * Returns address fields’ HTML (sans country) for the given country and subdivisions.
     *
     * @param string $namespace
     * @param string $countryCode
     * @param string|null $administrativeArea
     * @param string|null $locality
     * @return Response
     */
    public function actionFields(
        string $namespace,
        string $countryCode,
        ?string $administrativeArea = null,
        ?string $locality = null,
    ): Response {
        $address = new Address([
            'countryCode' => $countryCode,
            'administrativeArea' => $administrativeArea,
            'locality' => $locality,
        ]);

        $html = $this->getView()->namespaceInputs(fn() => Cp::addressFieldsHtml($address), $namespace);

        return $this->asJson([
            'fieldsHtml' => $html,
            'headHtml' => $this->getView()->getHeadHtml(),
            'bodyHtml' => $this->getView()->getBodyHtml(),
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
