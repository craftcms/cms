<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Edit controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.13
 */
class EditController extends Controller
{
    /**
     * Redirects to an element’s edit page by its ID.
     *
     * @param int $id The element ID
     * @param string|null $site The handle of the site to fetch the element in
     * @return Response
     * @throws BadRequestHttpException if this is a site request, or `$site` is an invalid site handle
     * @throws NotFoundHttpException if `$id` is invalid
     * @throws ForbiddenHttpException if the user isn’t allowed to edit any sites or the requested element
     * @throws ServerErrorHttpException if the requested element doesn’t have an edit page
     */
    public function actionById(int $id, string $site = null): Response
    {
        $this->requireCpRequest();
        $element = Craft::$app->getElements()->getElementById($id, null, $this->_siteId($site));
        if (!$element) {
            throw new NotFoundHttpException("Invalid element ID: $id");
        }
        return $this->_redirectToElement($element);
    }

    /**
     * Redirects to an element’s edit page by its UID.
     *
     * @param string $uid The element UID
     * @param string|null $site The handle of the site to fetch the element in
     * @return Response
     * @throws BadRequestHttpException if this is a site request, or `$site` is an invalid site handle
     * @throws NotFoundHttpException if `$uid` is invalid
     * @throws ForbiddenHttpException if the user isn’t allowed to edit any sites or the requested element
     * @throws ServerErrorHttpException if the requested element doesn’t have an edit page
     */
    public function actionByUid(string $uid, string $site = null): Response
    {
        $this->requireCpRequest();
        $element = Craft::$app->getElements()->getElementByUid($uid, null, $this->_siteId($site));
        if (!$element) {
            throw new NotFoundHttpException("Invalid element UID: $uid");
        }
        return $this->_redirectToElement($element);
    }

    /**
     * Redirects to an element’s edit page.
     *
     * @param ElementInterface $element
     * @return Response
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    private function _redirectToElement(ElementInterface $element): Response
    {
        if (!$element->getIsEditable()) {
            throw new ForbiddenHttpException();
        }

        $url = $element->getCpEditUrl();

        if (!$url) {
            throw new ServerErrorHttpException('The element doesn’t have an edit page.');
        }

        return $this->redirect($url);
    }

    /**
     * Returns the site ID(s) that should be passed to `getElementById()` / `getElementByUid()`.
     *
     * @param string|null $site
     * @return int|int[]
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _siteId(string $site = null)
    {
        if ($site) {
            $siteHandle = $site;
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site handle: $siteHandle");
            }
            return $site->id;
        }

        $siteIds = Craft::$app->getSites()->getEditableSiteIds();
        if (empty($siteIds)) {
            throw new ForbiddenHttpException();
        }
        return $siteIds;
    }
}
