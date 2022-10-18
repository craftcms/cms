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
use craft\models\FieldLayoutTab;
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
    public function beforeAction($action): bool
    {
        // All field actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
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
            return $this->asModelFailure($group);
        }

        return $this->asModelSuccess($group, modelName: 'group');
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

        return $success ?
            $this->asSuccess(Craft::t('app', 'Group deleted.')) :
            $this->asFailure();
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
    public function actionEditField(?int $fieldId = null, ?FieldInterface $field = null, ?int $groupId = null): Response
    {
        $this->requireAdmin();

        $fieldsService = Craft::$app->getFields();

        // The field
        // ---------------------------------------------------------------------

        if ($field === null && $fieldId !== null) {
            $field = $fieldsService->getFieldById($fieldId);

            if ($field === null) {
                throw new NotFoundHttpException('Field not found');
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
        $foundCurrent = false;
        $missingFieldPlaceholder = null;

        foreach ($allFieldTypes as $class) {
            $isCurrent = $class === ($field instanceof MissingField ? $field->expectedType : get_class($field));
            $foundCurrent = $foundCurrent || $isCurrent;

            if ($isCurrent || $class::isSelectable()) {
                $compatible = $isCurrent || in_array($class, $compatibleFieldTypes, true);
                $fieldTypeOptions[] = [
                    'value' => $class,
                    'label' => $class::displayName() . ($compatible ? '' : ' ⚠️'),
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypeOptions, 'label');

        if ($field instanceof MissingField) {
            if ($foundCurrent) {
                $field = $fieldsService->createField($field->expectedType);
            } else {
                array_unshift($fieldTypeOptions, ['value' => $field->expectedType, 'label' => '']);
                $missingFieldPlaceholder = $field->getPlaceholderHtml();
            }
        }

        // Groups
        // ---------------------------------------------------------------------

        $allGroups = $fieldsService->getAllGroups();

        if (empty($allGroups)) {
            throw new ServerErrorHttpException('No field groups exist');
        }

        if ($groupId === null && isset($field->groupId)) {
            $groupId = $field->groupId;
        }

        if ($groupId) {
            $fieldGroup = $fieldsService->getGroupById($groupId);
            if ($fieldGroup === null) {
                throw new NotFoundHttpException('Field group not found');
            }
        } elseif (!$field->id && !$field->hasErrors()) {
            $fieldGroup = reset($allGroups);
        } else {
            $fieldGroup = null;
        }

        $groupOptions = [];

        if (!$fieldGroup) {
            $groupOptions[] = ['value' => '', 'label' => ''];
        }

        foreach ($allGroups as $group) {
            $groupOptions[] = [
                'value' => $group->id,
                'label' => $group->name,
            ];
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Fields'),
                'url' => UrlHelper::url('settings/fields'),
            ],
        ];

        if ($fieldGroup) {
            $crumbs[] = [
                'label' => Craft::t('site', $fieldGroup->name),
                'url' => UrlHelper::url('settings/fields/' . $groupId),
            ];
        }

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

        return $this->renderTemplate('settings/fields/_edit.twig', compact(
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
        $html = $view->renderTemplate('settings/fields/_type-settings.twig', [
            'field' => $field,
            'namespace' => $this->request->getBodyParam('namespace'),
        ]);

        return $this->asJson([
            'settingsHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Saves a field.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveField(): ?Response
    {
        $this->requirePostRequest();

        $fieldsService = Craft::$app->getFields();
        $type = $this->request->getRequiredBodyParam('type');
        $fieldId = $this->request->getBodyParam('fieldId') ?: null;

        if ($fieldId) {
            $oldField = clone Craft::$app->getFields()->getFieldById($fieldId);
            if (!$oldField) {
                throw new BadRequestHttpException("Invalid field ID: $fieldId");
            }
            $fieldUid = $oldField->uid;
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
            'columnSuffix' => $oldField->columnSuffix ?? null,
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
                'field' => $field,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Field saved.'));
        return $this->redirectToPostedUrl($field);
    }

    /**
     * Deletes a field.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDeleteField(): ?Response
    {
        $this->requirePostRequest();

        $fieldId = $this->request->getBodyParam('fieldId') ?? $this->request->getRequiredBodyParam('id');
        $fieldsService = Craft::$app->getFields();
        /** @var FieldInterface|Field|null $field */
        $field = $fieldsService->getFieldById($fieldId);

        if (!$field) {
            throw new BadRequestHttpException("Invalid field ID: $fieldId");
        }

        if (!$fieldsService->deleteField($field)) {
            return $this->asModelFailure($field, Craft::t('app', 'Couldn’t delete “{name}”.', [
                'name' => $field->name,
            ]));
        }

        return $this->asModelSuccess($field, Craft::t('app', '“{name}” deleted.', [
            'name' => $field->name,
        ]));
    }

    // Field Layouts
    // -------------------------------------------------------------------------

    /**
     * Applies a field layout tab’s settings.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 4.0.0
     */
    public function actionApplyLayoutTabSettings(): Response
    {
        $tab = new FieldLayoutTab($this->_fldComponentConfig());

        return $this->asJson([
            'config' => $tab->toArray(),
        ]);
    }

    /**
     * Applies a field layout element’s settings.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 4.0.0
     */
    public function actionApplyLayoutElementSettings(): Response
    {
        $element = Craft::$app->getFields()->createLayoutElement($this->_fldComponentConfig());

        return $this->asJson([
            'config' => ['type' => get_class($element)] + $element->toArray(),
            'selectorHtml' => $element->selectorHtml(),
            'hasConditions' => $element->hasConditions(),
        ]);
    }

    /**
     * Returns the posted settings.
     *
     * @return array
     */
    private function _fldComponentConfig(): array
    {
        $config = $this->request->getRequiredBodyParam('config');
        $settingsNamespace = $this->request->getRequiredBodyParam('settingsNamespace');
        $settingsStr = $this->request->getRequiredBodyParam('settings');
        parse_str($settingsStr, $settings);
        return array_merge($config, ArrayHelper::getValue($settings, $settingsNamespace, []));
    }
}
