<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Chippable;
use craft\base\Colorable;
use craft\base\CpEditable;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\FieldLayoutComponent;
use craft\base\FieldLayoutElement;
use craft\base\FieldLayoutProviderInterface;
use craft\base\Iconic;
use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\fields\MissingField;
use craft\fields\PlainText;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\Typecast;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\web\assets\fieldsettings\FieldSettingsAsset;
use craft\web\Controller;
use ReflectionException;
use ReflectionProperty;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The FieldsController class is a controller that handles various field-related tasks.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldsController extends Controller
{
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $viewActions = ['edit-field', 'table-data'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return true;
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Edits a field.
     *
     * @param int|null $fieldId The field’s ID, if editing an existing field
     * @param FieldInterface|null $field The field being edited, if there were any validation errors
     * @param string|null $type The field type to use by default
     * @return Response
     */
    public function actionEditField(?int $fieldId = null, ?FieldInterface $field = null, ?string $type = null): Response
    {
        if ($fieldId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

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
            $field = $fieldsService->createField($type ?? PlainText::class);
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
        $fieldTypeNames = [];
        $foundCurrent = false;
        $missingFieldPlaceholder = null;
        $multiInstanceTypesOnly = (bool)$this->request->getParam('multiInstanceTypesOnly');

        foreach ($allFieldTypes as $class) {
            $isCurrent = $class === ($field instanceof MissingField ? $field->expectedType : get_class($field));
            $foundCurrent = $foundCurrent || $isCurrent;

            if (
                $isCurrent ||
                (
                    $class::isSelectable() &&
                    (!$multiInstanceTypesOnly || $class::isMultiInstance())
                )
            ) {
                $compatible = $isCurrent || in_array($class, $compatibleFieldTypes, true);
                $name = $class::displayName();
                $option = [
                    'icon' => $isCurrent && $field instanceof Iconic ? $field->getIcon() : $class::icon(),
                    'value' => $class,
                ];
                if ($compatible) {
                    $option['label'] = $name;
                } else {
                    $option['labelHtml'] = Html::beginTag('div', ['class' => 'inline-flex']) .
                        Html::tag('span', Html::encode($name)) .
                        Html::tag('span', Cp::iconSvg('triangle-exclamation'), ['class' => ['cp-icon', 'small', 'warning']]) .
                        Html::endTag('div');
                }
                $fieldTypeOptions[] = $option;
                $fieldTypeNames[] = $name;
            }
        }

        // Sort them by name
        array_multisort($fieldTypeNames, $fieldTypeOptions);

        if ($field instanceof MissingField) {
            if ($foundCurrent) {
                $field = $fieldsService->createField($field->expectedType);
            } else {
                array_unshift($fieldTypeOptions, ['value' => $field->expectedType, 'label' => '']);
                $missingFieldPlaceholder = $field->getPlaceholderHtml();
            }
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        if ($fieldId !== null) {
            $title = trim($field->name) ?: Craft::t('app', 'Edit Field');
        } else {
            $title = Craft::t('app', 'Create a new field');
        }

        $response = $this->asCpScreen()
            ->title($title)
            ->addCrumb(Craft::t('app', 'Settings'), 'settings')
            ->addCrumb(Craft::t('app', 'Fields'), 'settings/fields')
            ->contentTemplate('settings/fields/_edit.twig', [
                'fieldId' => $fieldId,
                'field' => $field,
                'fieldTypeOptions' => $fieldTypeOptions,
                'missingFieldPlaceholder' => $missingFieldPlaceholder,
                'supportedTranslationMethods' => $supportedTranslationMethods,
                'readOnly' => $this->readOnly,
            ]);

        if (!$this->readOnly) {
            $response
                ->action('fields/save-field')
                ->redirectUrl(UrlHelper::cpReferralUrl() ?? 'settings/fields')
                ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                    'redirect' => 'settings/fields/edit/{id}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ])
                ->addAltAction(Craft::t('app', 'Save and add another'), [
                    'shortcut' => true,
                    'shift' => true,
                    'params' => ['addAnother' => 1],
                ])
                ->editUrl($field->id ? "settings/fields/edit/$field->id" : null);
        } else {
            $response->noticeHtml(Cp::readOnlyNoticeHtml());
        }

        $response
                ->prepareScreen(function() {
                    $view = Craft::$app->getView();
                    $view->registerAssetBundle(FieldSettingsAsset::class);
                    $view->registerJsWithVars(fn($typeId, $settingsId, $namespace) => <<<JS
new Craft.FieldSettingsToggle('#' + $typeId, '#' + $settingsId, $namespace, {
  wrapWithTypeClassDiv: true
});
JS, [
                        $view->namespaceInputId('type'),
                        $view->namespaceInputId('settings'),
                        $view->namespaceInputName('types[__TYPE__]'),
                    ]);
                });

        if ($field->id) {
            if (!$this->readOnly) {
                $response
                    ->addAltAction(Craft::t('app', 'Delete'), [
                        'action' => 'fields/delete-field',
                        'redirect' => 'settings/fields',
                        'destructive' => true,
                        'confirm' => Craft::t('app', 'Are you sure you want to delete “{name}”?', [
                            'name' => $field->name,
                        ]),
                    ]);
            }
            $response
                ->metaSidebarHtml(Cp::metadataHtml([
                Craft::t('app', 'ID') => $field->id,
                Craft::t('app', 'Used by') => function() use ($fieldsService, $field) {
                    $layouts = $fieldsService->findFieldUsages($field);
                    if (empty($layouts)) {
                        return Html::tag('i', Craft::t('app', 'No usages'));
                    }

                    /** @var FieldLayout[][] $layoutsByType */
                    $layoutsByType = ArrayHelper::index($layouts,
                        fn(FieldLayout $layout) => $layout->uid,
                        [fn(FieldLayout $layout) => $layout->type ?? '__UNKNOWN__'],
                    );
                    /** @var FieldLayout[] $unknownLayouts */
                    $unknownLayouts = ArrayHelper::remove($layoutsByType, '__UNKNOWN__');
                    /** @var FieldLayout[] $layoutsWithProviders */
                    $layoutsWithProviders = [];

                    // re-fetch as many of these as we can from the element types,
                    // so they have a chance to supply the layout providers
                    foreach ($layoutsByType as $type => &$typeLayouts) {
                        /** @var class-string<ElementInterface> $type */
                        /** @phpstan-ignore-next-line */
                        foreach ($type::fieldLayouts(null) as $layout) {
                            if (isset($typeLayouts[$layout->uid]) && $layout->provider instanceof Chippable) {
                                $layoutsWithProviders[] = $layout;
                                unset($typeLayouts[$layout->uid]);
                            }
                        }
                    }
                    unset($typeLayouts);

                    $labels = [];
                    $items = array_map(function(FieldLayout $layout) use (&$labels) {
                        /** @var FieldLayoutProviderInterface&Chippable $provider */
                        $provider = $layout->provider;
                        $label = $labels[] = $provider->getUiLabel();
                        // special case for global sets, where we should link to the settings rather than the edit page
                        if ($provider instanceof GlobalSet) {
                            $url = "settings/globals/$provider->id";
                        } else {
                            $url = $provider instanceof CpEditable ? $provider->getCpEditUrl() : null;
                        }
                        $icon = $provider instanceof Iconic ? $provider->getIcon() : null;

                        $labelHtml = Html::beginTag('span', [
                            'class' => ['flex', 'flex-nowrap', 'gap-s'],
                        ]);
                        if ($icon) {
                            $labelHtml .= Html::tag('div', Cp::iconSvg($icon), [
                                'class' => array_filter([
                                    'cp-icon',
                                    'small',
                                    $provider instanceof Colorable ? $provider->getColor()?->value : null,
                                ]),
                            ]);
                        }
                        $labelHtml .= Html::tag('span', Html::encode($label)) .
                            Html::endTag('span');

                        return $url ? Html::a($labelHtml, $url) : $labelHtml;
                    }, $layoutsWithProviders);

                    // sort by label
                    array_multisort($labels, SORT_ASC, $items);

                    foreach ($layoutsByType as $type => $typeLayouts) {
                        // any remaining layouts for this type?
                        if (!empty($typeLayouts)) {
                            /** @var class-string<ElementInterface> $type */
                            $items[] = Craft::t('app', '{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                                'total' => count($typeLayouts),
                                'type' => $type::lowerDisplayName(),
                            ]);
                        }
                    }

                    if (!empty($unknownLayouts)) {
                        $items[] = Craft::t('app', '{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                            'total' => count($unknownLayouts),
                            'type' => Craft::t('app', 'unknown'),
                        ]);
                    }

                    return Html::ul($items, [
                        'encode' => false,
                    ]);
                },
            ]));
        }

        return $response;
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

        /** @var class-string<FieldInterface> $type */
        $type = $this->request->getRequiredBodyParam('type');
        $field = Craft::$app->getFields()->createField($type);

        /** @var class-string<FieldInterface>|null $oldType */
        $oldType = $this->request->getBodyParam('oldType');
        if ($oldType && Component::validateComponentClass($oldType, FieldInterface::class)) {
            $settingsStr = $this->request->getBodyParam('settings');
            parse_str($settingsStr, $postedOldSettings);
            $oldNamespace = $this->request->getBodyParam('oldNamespace');
            $settings = ArrayHelper::getValue($postedOldSettings, $oldNamespace, []);

            // Remove any settings that aren't defined by the same class between both types
            $settings = array_filter($settings, function($attribute) use ($type, $oldType) {
                try {
                    $r1 = new ReflectionProperty($type, $attribute);
                    $r2 = new ReflectionProperty($oldType, $attribute);
                    return $r1->getDeclaringClass()->name === $r2->getDeclaringClass()->name;
                } catch (ReflectionException) {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);

            Typecast::properties($type, $settings);
            Craft::configure($field, $settings);
        }

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
            $oldField = Craft::$app->getFields()->getFieldById($fieldId);
            if (!$oldField) {
                throw new BadRequestHttpException("Invalid field ID: $fieldId");
            }
            $oldField = clone $oldField;
            $fieldUid = $oldField->uid;
        } else {
            $fieldUid = null;
        }

        $field = $fieldsService->createField([
            'type' => $type,
            'id' => $fieldId,
            'uid' => $fieldUid,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'columnSuffix' => $oldField->columnSuffix ?? null,
            'instructions' => $this->request->getBodyParam('instructions'),
            'searchable' => (bool)$this->request->getBodyParam('searchable', true),
            'translationMethod' => $this->request->getBodyParam('translationMethod', Field::TRANSLATION_METHOD_NONE),
            'translationKeyFormat' => $this->request->getBodyParam('translationKeyFormat'),
            'settings' => $this->request->getBodyParam(sprintf('types.%s', Html::id($type))),
        ]);

        if (!$fieldsService->saveField($field)) {
            return $this->asModelFailure($field, Craft::t('app', 'Couldn’t save field.'), 'field');
        }

        if ($this->request->getParam('addAnother')) {
            $redirect = UrlHelper::cpUrl('settings/fields/new', [
                'type' => $field::class,
            ]);
        } else {
            $redirect = null;
        }

        return $this->asModelSuccess($field, Craft::t('app', 'Field saved.'), 'field', [
            'selectorHtml' => Cp::layoutElementSelectorHtml(new CustomField($field), true),
        ], $redirect);
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
     * Renders a field layout component’s settings.
     *
     * @since 5.1.0
     */
    public function actionRenderLayoutComponentSettings(): Response
    {
        $element = $this->_fldComponent();
        $namespace = StringHelper::randomString(10);
        $view = Craft::$app->getView();
        $html = $view->namespaceInputs(fn() => $element->getSettingsHtml(), $namespace);

        return $this->asJson([
            'settingsHtml' => $html,
            'namespace' => $namespace,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Applies a field layout tab’s settings.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 4.0.0
     */
    public function actionApplyLayoutTabSettings(): Response
    {
        /** @var FieldLayoutTab $tab */
        $tab = $this->_fldComponent();

        return $this->asJson([
            'config' => $tab->toArray(),
            'labelHtml' => $tab->labelHtml(),
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
        /** @var FieldLayoutElement $element */
        $element = $this->_fldComponent($settings);

        if (!empty($settings)) {
            $validateAttributes = array_intersect(
                array_keys(array_filter($settings)),
                ['name', 'handle', 'instructions'],
            );
        }

        if (!empty($validateAttributes) && $element instanceof CustomField) {
            $field = $element->getField();
            if ($field instanceof Field) {
                $field->validateHandleUniqueness = false;
            }

            if (!$field->validate($validateAttributes)) {
                if ($field->hasErrors('name')) {
                    $field->addErrors(['label' => $field->getErrors('name')]);
                    $field->clearErrors('name');
                }
                return $this->asModelFailure($field, Craft::t('app', 'Couldn’t apply changes.'), 'field');
            }
        }

        $selectorHtml = Cp::layoutElementSelectorHtml($element);

        return $this->asJson([
            'config' => ['type' => get_class($element)] + $element->toArray(),
            'selectorHtml' => $selectorHtml,
        ]);
    }

    /**
     * Returns data formatted for AdminTable vue component
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();

        $fieldsService = Craft::$app->getFields();

        $page = (int)$this->request->getParam('page', 1);
        $limit = (int)$this->request->getParam('per_page', 100);
        $searchTerm = $this->request->getParam('search');
        $orderBy = match ($this->request->getParam('sort.0.field')) {
            '__slot:handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };
        $sortDir = match ($this->request->getParam('sort.0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $fieldsService->getTableData($page, $limit, $searchTerm, $orderBy, $sortDir);

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

    /**
     * Returns card preview HTML data.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionRenderCardPreview()
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $fieldLayoutConfig = $this->request->getRequiredBodyParam('fieldLayoutConfig');
        $fieldLayout = Craft::$app->getFields()->createLayout($fieldLayoutConfig);

        return $this->asJson([
            'previewHtml' => Cp::cardPreviewHtml($fieldLayout),
        ]);
    }

    /**
     * Returns the field layout component being edited, populated with the posted config/settings.
     *
     * @param array|null $settings The `settings` array that might have been posted
     * @return FieldLayoutComponent
     */
    private function _fldComponent(?array &$settings = null): FieldLayoutComponent
    {
        $uid = $this->request->getRequiredBodyParam('uid');
        $elementType = $this->request->getRequiredBodyParam('elementType');
        $layoutConfig = Component::cleanseConfig($this->request->getRequiredBodyParam('layoutConfig'));

        if (!isset($layoutConfig['tabs'])) {
            throw new BadRequestHttpException('Layout config doesn’t have any tabs.');
        }

        $layoutConfig['type'] = $elementType;

        $componentConfig = $this->request->getBodyParam('config') ?? [];
        $componentConfig['elementType'] = $elementType;
        $settingsStr = $this->request->getBodyParam('settings');

        if ($settingsStr !== null) {
            parse_str($settingsStr, $postedSettings);
            $settingsNamespace = $this->request->getRequiredBodyParam('settingsNamespace');
            $settings = ArrayHelper::getValue($postedSettings, $settingsNamespace, []);
            $componentConfig = array_merge($componentConfig, $settings);
        }

        $componentConfig = Component::cleanseConfig($componentConfig);

        $isTab = false;

        foreach ($layoutConfig['tabs'] as &$tabConfig) {
            if (isset($tabConfig['uid']) && $tabConfig['uid'] === $uid) {
                $isTab = true;
                $tabConfig = array_merge($tabConfig, $componentConfig);
                break;
            }

            foreach ($tabConfig['elements'] as &$elementConfig) {
                if (isset($elementConfig['uid']) && $elementConfig['uid'] === $uid) {
                    $elementConfig = array_merge($elementConfig, $componentConfig);
                    break 2;
                }
            }
        }

        $layout = Craft::$app->getFields()->createLayout($layoutConfig);

        if ($isTab) {
            foreach ($layout->getTabs() as $tab) {
                if ($tab->uid === $uid) {
                    return $tab;
                }
            }

            throw new BadRequestHttpException("Invalid layout tab UUID: $uid");
        }

        $element = $layout->getElementByUid($uid);
        if (!$element) {
            throw new BadRequestHttpException("Invalid layout element UUID: $uid");
        }
        return $element;
    }
}
