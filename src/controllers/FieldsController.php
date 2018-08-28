<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\fields\MissingField;
use craft\fields\PlainText;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldGroup;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public function actionSaveGroup(): Response
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
        }

        return $this->asJson([
            'errors' => $group->getErrors(),
        ]);
    }

    /**
     * Deletes a field group.
     *
     * @return Response
     */
    public function actionDeleteGroup(): Response
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
     * @param int|null $fieldId The field’s ID, if editing an existing field
     * @param FieldInterface|null $field The field being edited, if there were any validation errors
     * @param int|null $groupId The default group ID that the field should be saved in
     * @return Response
     * @throws NotFoundHttpException if the requested field/field group cannot be found
     * @throws ServerErrorHttpException if no field groups exist
     */
    public function actionEditField(int $fieldId = null, FieldInterface $field = null, int $groupId = null): Response
    {
        $this->requireAdmin();

        $fieldsService = Craft::$app->getFields();

        // The field
        // ---------------------------------------------------------------------

        $missingFieldPlaceholder = null;

        /** @var Field $field */
        if ($field === null && $fieldId !== null) {
            $field = $fieldsService->getFieldById($fieldId);

            if ($field === null) {
                throw new NotFoundHttpException('Field not found');
            }

            if ($field instanceof MissingField) {
                $missingFieldPlaceholder = $field->getPlaceholderHtml();
                $field = $field->createFallback(PlainText::class);
            }
        }

        if ($field === null) {
            $field = $fieldsService->createField(PlainText::class);
        }

        // Supported translation methods
        // ---------------------------------------------------------------------

        $supportedTranslationMethods = [];
        /** @var string[]|FieldInterface[] $allFieldTypes */
        $allFieldTypes = $fieldsService->getAllFieldTypes();

        foreach ($allFieldTypes as $class) {
            if ($class === get_class($field) || $class::isSelectable()) {
                $supportedTranslationMethods[$class] = $class::supportedTranslationMethods();
            }
        }

        // Allowed field types
        // ---------------------------------------------------------------------

        if (!$field->id) {
            $compatibleFieldTypes = $allFieldTypes;
        } else {
            $compatibleFieldTypes = $fieldsService->getCompatibleFieldTypes($field, true);
        }

        /** @var string[]|FieldInterface[] $compatibleFieldTypes */
        $fieldTypeOptions = [];

        foreach ($allFieldTypes as $class) {
            if ($class === get_class($field) || $class::isSelectable()) {
                $compatible = in_array($class, $compatibleFieldTypes, true);
                $fieldTypeOptions[] = [
                    'value' => $class,
                    'label' => $class::displayName() . ($compatible ? '' : ' ⚠️'),
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypeOptions, 'label');

        // Groups
        // ---------------------------------------------------------------------

        $allGroups = $fieldsService->getAllGroups();

        if (empty($allGroups)) {
            throw new ServerErrorHttpException('No field groups exist');
        }

        if ($groupId === null) {
            $groupId = ($field !== null && $field->groupId !== null) ? $field->groupId : $allGroups[0]->id;
        }

        $fieldGroup = $fieldsService->getGroupById($groupId);

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
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Fields'),
                'url' => UrlHelper::url('settings/fields')
            ],
            [
                'label' => Craft::t('site', $fieldGroup->name),
                'url' => UrlHelper::url('settings/fields/' . $groupId)
            ],
        ];

        if ($fieldId !== null) {
            $title = $field->name;
        } else {
            $title = Craft::t('app', 'Create a new field');
        }

        return $this->renderTemplate('settings/fields/_edit', compact(
            'fieldId',
            'field',
            'allFieldTypes',
            'fieldTypeOptions',
            'missingFieldPlaceholder',
            'supportedTranslationMethods',
            'compatibleFieldTypes',
            'groupId',
            'groupOptions',
            'crumbs',
            'title'
        ));
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
            'settings' => $request->getBodyParam('types.' . $type),
        ]);

        if (!$fieldsService->saveField($field)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save field.'));

            // Send the field back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'field' => $field
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Field saved.'));

        return $this->redirectToPostedUrl($field);
    }

    /**
     * Deletes a field.
     *
     * @return Response
     */
    public function actionDeleteField(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $fieldId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $success = Craft::$app->getFields()->deleteFieldById($fieldId);

        return $this->asJson(['success' => $success]);
    }
}
