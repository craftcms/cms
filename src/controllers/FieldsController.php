<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\helpers\Url;
use craft\app\models\FieldGroup;
use craft\app\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All field actions require an admin
        $this->requireAdmin();
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Saves a field group.
     *
     * @return Response
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $group = new FieldGroup();
        $group->id = Craft::$app->getRequest()->getBodyParam('id');
        $group->name = Craft::$app->getRequest()->getRequiredBodyParam('name');

        $isNewGroup = empty($group->id);

        if (Craft::$app->getFields()->saveGroup($group)) {
            if ($isNewGroup) {
                Craft::$app->getSession()->setNotice(Craft::t('app', 'Group added.'));
            }

            return $this->asJson([
                'success' => true,
                'group' => $group->getAttributes(),
            ]);
        } else {
            return $this->asJson([
                'errors' => $group->getErrors(),
            ]);
        }
    }

    /**
     * Deletes a field group.
     *
     * @return Response
     */
    public function actionDeleteGroup()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $success = Craft::$app->getFields()->deleteGroupById($groupId);

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Group deleted.'));

        return $this->asJson([
            'success' => $success,
        ]);
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Edits a field.
     *
     * @param integer        $fieldId The field’s ID, if editing an existing field
     * @param FieldInterface $field   The field being edited, if there were any validation errors
     * @param integer        $groupId The default group ID that the field should be saved in
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested field/field group cannot be found
     * @throws ServerErrorHttpException if no field groups exist
     */
    public function actionEditField($fieldId = null, FieldInterface $field = null, $groupId = null)
    {
        $this->requireAdmin();

        // The field
        // ---------------------------------------------------------------------

        if ($field === null && $fieldId !== null) {
            $field = Craft::$app->getFields()->getFieldById($fieldId);

            if ($field === null) {
                throw new NotFoundHttpException('Field not found');
            }
        }

        if ($field === null) {
            $field = Craft::$app->getFields()->createField(\craft\app\fields\PlainText::class);
        }

        /** @var Field $field */

        // Field types
        // ---------------------------------------------------------------------

        $allFieldTypes = Craft::$app->getFields()->getAllFieldTypes();
        $fieldTypeOptions = [];

        foreach ($allFieldTypes as $class) {
            if ($class === $field->getType() || $class::isSelectable()) {
                $fieldTypeOptions[] = [
                    'value' => $class,
                    'label' => $class::displayName()
                ];
            }
        }

        // Groups
        // ---------------------------------------------------------------------

        $allGroups = Craft::$app->getFields()->getAllGroups();

        if (empty($allGroups)) {
            throw new ServerErrorHttpException('No field groups exist');
        }

        if ($groupId === null) {
            $groupId = ($field !== null && $field->groupId !== null) ? $field->groupId : $allGroups[0]->id;
        }

        $fieldGroup = Craft::$app->getFields()->getGroupById($groupId);

        if ($fieldGroup === null) {
            throw new NotFoundHttpException('Field group not found');
        }

        $groupOptions = [];

        foreach ($allGroups as $group) {
            $groupOptions[] = [
                'value' => $group->id,
                'label' => $group->name
            ];
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => Url::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Fields'),
                'url' => Url::getUrl('settings/fields')
            ],
            [
                'label' => Craft::t('site', $fieldGroup->name),
                'url' => Url::getUrl('settings/fields/'.$groupId)
            ],
        ];

        if ($fieldId !== null) {
            $title = $field->name;
        } else {
            $title = Craft::t('app', 'Create a new field');
        }

        return $this->renderTemplate('settings/fields/_edit', [
            'fieldId' => $fieldId,
            'field' => $field,
            'fieldTypeOptions' => $fieldTypeOptions,
            'allFieldTypes' => $allFieldTypes,
            'groupId' => $groupId,
            'groupOptions' => $groupOptions,
            'crumbs' => $crumbs,
            'title' => $title,
            'docsUrl' => 'http://craftcms.com/docs/fields#field-layouts',
        ]);
    }

    /**
     * Saves a field.
     *
     * @return Response|null
     */
    public function actionSaveField()
    {
        $this->requirePostRequest();

        $fieldsService = Craft::$app->getFields();
        $request = Craft::$app->getRequest();
        $type = $request->getRequiredBodyParam('type');

        $field = $fieldsService->createField([
            'type' => $type,
            'id' => $request->getBodyParam('fieldId'),
            'groupId' => $request->getRequiredBodyParam('group'),
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'instructions' => $request->getBodyParam('instructions'),
            'translationMethod' => $request->getBodyParam('translationMethod', Field::TRANSLATION_METHOD_NONE),
            'translationKeyFormat' => $request->getBodyParam('translationKeyFormat'),
            'settings' => $request->getBodyParam('types.'.$type),
        ]);

        if ($fieldsService->saveField($field)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Field saved.'));

            return $this->redirectToPostedUrl($field);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save field.'));

        // Send the field back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'field' => $field
        ]);

        return null;
    }

    /**
     * Deletes a field.
     *
     * @return Response
     */
    public function actionDeleteField()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $fieldId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $success = Craft::$app->getFields()->deleteFieldById($fieldId);

        return $this->asJson(['success' => $success]);
    }
}
