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
use craft\db\Table;
use craft\fields\MissingField;
use craft\fields\PlainText;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\FieldGroup;
use craft\web\assets\fieldsettings\FieldSettingsAsset;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // All field actions require an admin
        $this->requireAdmin();
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Saves a field group.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSaveGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $fieldsService = Craft::$app->getFields();
        $groupId = $this->request->getBodyParam('id');

        if ($groupId) {
            $group = $fieldsService->getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid field group ID: $groupId");
            }
        } else {
            $group = new FieldGroup();
        }

        $group->name = $this->request->getRequiredBodyParam('name');

        if (!$fieldsService->saveGroup($group)) {
            return $this->asJson([
                'errors' => $group->getErrors(),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'group' => $group->getAttributes(),
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

        $groupId = $this->request->getRequiredBodyParam('id');
        $success = Craft::$app->getFields()->deleteGroupById($groupId);

        $this->setSuccessFlash(Craft::t('app', 'Group deleted.'));

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
            $title = trim($field->name) ?: Craft::t('app', 'Edit Field');
        } else {
            $title = Craft::t('app', 'Create a new field');
        }

        $js = <<<JS
new Craft.FieldSettingsToggle('#type', '#settings', 'types[__TYPE__]', {
    wrapWithTypeClassDiv: true
});
JS;

        $view = Craft::$app->getView();
        $view->registerAssetBundle(FieldSettingsAsset::class);
        $view->registerJs($js);

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
     * Renders a field's settings.
     *
     * @return Response
     * @since 3.4.22
     */
    public function actionRenderSettings(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $type = $this->request->getRequiredBodyParam('type');
        $field = Craft::$app->getFields()->createField($type);

        $view = Craft::$app->getView();
        $html = $view->renderTemplate('settings/fields/_type-settings', [
            'field' => $field,
            'namespace' => $this->request->getBodyParam('namespace')
        ]);

        return $this->asJson([
            'settingsHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Saves a field.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveField()
    {
        $this->requirePostRequest();

        $fieldsService = Craft::$app->getFields();
        $type = $this->request->getRequiredBodyParam('type');
        $fieldId = $this->request->getBodyParam('fieldId') ?: null;

        if ($fieldId) {
            $fieldUid = Db::uidById(Table::FIELDS, $fieldId);
            if (!$fieldUid) {
                throw new BadRequestHttpException("Invalid field ID: $fieldId");
            }
        } else {
            $fieldUid = null;
        }

        $field = $fieldsService->createField([
            'type' => $type,
            'id' => $fieldId,
            'uid' => $fieldUid,
            'groupId' => $this->request->getRequiredBodyParam('group'),
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'instructions' => $this->request->getBodyParam('instructions'),
            'searchable' => (bool)$this->request->getBodyParam('searchable', true),
            'translationMethod' => $this->request->getBodyParam('translationMethod', Field::TRANSLATION_METHOD_NONE),
            'translationKeyFormat' => $this->request->getBodyParam('translationKeyFormat'),
            'settings' => $this->request->getBodyParam('types.' . $type),
        ]);

        if (!$fieldsService->saveField($field)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save field.'));

            // Send the field back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'field' => $field
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Field saved.'));
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

        $fieldId = $this->request->getRequiredBodyParam('id');
        $success = Craft::$app->getFields()->deleteFieldById($fieldId);

        return $this->asJson(['success' => $success]);
    }

    // Field Layouts
    // -------------------------------------------------------------------------

    /**
     * Renders a field layout element’s selector HTML.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.5.0
     */
    public function actionRenderLayoutElementSelector(): Response
    {
        $config = $this->request->getRequiredBodyParam('config');
        $element = Craft::$app->getFields()->createLayoutElement($config);

        return $this->asJson([
            'html' => $element->selectorHtml(),
        ]);
    }
}
