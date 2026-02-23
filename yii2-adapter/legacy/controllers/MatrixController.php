<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\db\EntryQuery;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Class MatrixController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class MatrixController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        return true;
    }

    /**
     * Renders an updated “Default Table Columns” input for the selected entry types.
     *
     * @return Response
     */
    public function actionDefaultTableColumnOptions(): Response
    {
        $entryTypeIds = $this->request->getRequiredBodyParam('entryTypeIds');
        $entryTypes = [];

        foreach ($entryTypeIds as $entryTypeId) {
            $entryType = EntryTypes::getEntryTypeById($entryTypeId);
            if (!$entryType) {
                throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
            }
            $entryTypes[] = $entryType;
        }

        return $this->asJson([
            'options' => Matrix::defaultTableColumnOptions($entryTypes),
        ]);
    }

    /**
     * Creates a new entry and renders its block UI.
     *
     * @return Response
     */
    public function actionCreateEntry(): Response
    {
        $fieldId = $this->request->getRequiredBodyParam('fieldId');
        $entryTypeId = $this->request->getRequiredBodyParam('entryTypeId');
        $ownerId = $this->request->getRequiredBodyParam('ownerId');
        $ownerElementType = $this->request->getRequiredBodyParam('ownerElementType');
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $namespace = $this->request->getRequiredBodyParam('namespace');
        $staticEntries = $this->request->getBodyParam('staticEntries', false);

        $elementsService = Craft::$app->getElements();
        $owner = $elementsService->getElementById($ownerId, $ownerElementType, $siteId);
        if (!$owner) {
            throw new BadRequestHttpException("Invalid owner ID, element type, or site ID.");
        }

        $field = $owner->getFieldLayout()?->getFieldById($fieldId);
        if (!$field instanceof Matrix) {
            throw new BadRequestHttpException("Invalid Matrix field ID: $fieldId");
        }

        $entryType = EntryTypes::getEntryTypeById($entryTypeId);
        if (!$entryType) {
            throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
        }

        $site = Sites::getSiteById($siteId, true);
        if (!$site) {
            throw new BadRequestHttpException("Invalid site ID: $siteId");
        }

        $attributes = [
            'siteId' => $siteId,
            'uid' => Str::uuid()->toString(),
            'typeId' => $entryType->id,
            'fieldId' => $fieldId,
            'primaryOwner' => $owner,
            'owner' => $owner,
            'slug' => ElementHelper::tempSlug(),
        ];

        $user = static::currentUser();

        // duplicate an existing entry?
        $sourceId = $this->request->getBodyParam('duplicate');
        if ($sourceId) {
            /** @var ?Entry $source */
            $source = Entry::find()
                ->id($sourceId)
                ->siteId($siteId)
                ->drafts(null)
                ->status(null)
                ->one();

            if (!$source) {
                throw new BadRequestHttpException("Invalid source element ID: $sourceId");
            }

            // set owner so that the canDuplicateAsDraft checks the max entries on the right owner and not only the canonical
            $source->setOwner($owner);

            if (!$elementsService->canDuplicateAsDraft($source, $user)) {
                throw new ForbiddenHttpException('User not authorized to duplicate this element.');
            }

            try {
                $entry = $elementsService->duplicateElement($source, [
                    ...$attributes,
                    'isProvisionalDraft' => false,
                    'draftId' => null,
                    'sortOrder' => null,
                ]);
            } catch (InvalidElementException $e) {
                return $this->asFailure(t('Couldn’t duplicate {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ]));
            } catch (Throwable $e) {
                throw new ServerErrorHttpException('An error occurred when duplicating the element.', 0, $e);
            }
        } else {
            /** @var Entry $entry */
            $entry = Craft::createObject([
                'class' => Entry::class,
                ...$attributes,
            ]);

            if (!$elementsService->canSave($entry, $user)) {
                throw new ForbiddenHttpException('User not authorized to create this element.');
            }

            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
            if (!app(Drafts::class)->saveElementAsDraft($entry, $user->id, markAsSaved: false)) {
                return $this->asFailure(mb_ucfirst(t('Couldn’t create {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ])));
            }
        }

        /** @var EntryQuery|ElementCollection $value */
        $value = $owner->getFieldValue($field->handle);

        $view = $this->getView();
        /** @var Entry[] $entries */
        $entries = $value->all();
        $html = InputNamespace::namespaceInputs(fn() => template('_components/fieldtypes/Matrix/block', [
            'name' => $field->handle,
            'entryTypes' => $field->getEntryTypesForField($entries, $owner),
            'entry' => $entry,
            'isFresh' => true,
            'staticEntries' => $staticEntries,
        ]), $namespace);

        return $this->asJson([
            'blockHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Renders the blocks for newly-created entries.
     *
     * @return Response
     * @since 5.7.0
     */
    public function actionRenderBlocks(): Response
    {
        $entryIds = $this->request->getRequiredBodyParam('entryIds');
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $namespace = $this->request->getRequiredBodyParam('namespace');

        if (empty($entryIds)) {
            throw new BadRequestHttpException('Request missing required body param');
        }

        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->id($entryIds)
            ->fixedOrder()
            ->siteId($siteId)
            ->status(null)
            ->all();

        $elementsService = Craft::$app->getElements();
        $view = $this->getView();
        $field = null;
        $entryTypes = null;
        $html = '';

        if (!empty($entries)) {
            foreach ($entries as $entry) {
                $field ??= $entry->getField();
                if (!$field instanceof Matrix || $field->id !== $entry->fieldId) {
                    throw new BadRequestHttpException('Entry must belong to a Matrix field.');
                }
                $entryTypes ??= $field->getEntryTypesForField($entries, $entry->getOwner());
                if (!$elementsService->canView($entry)) {
                    throw new ForbiddenHttpException('User not authorized to view this element.');
                }

                $html .= InputNamespace::namespaceInputs(fn() => template('_components/fieldtypes/Matrix/block', [
                    'name' => $field->handle,
                    'entryTypes' => $entryTypes,
                    'entry' => $entry,
                ]), $namespace);
            }
        }

        return $this->asJson([
            'blockHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }
}
