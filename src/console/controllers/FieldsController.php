<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\MergeableFieldInterface;
use craft\console\Controller;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\models\FieldLayout;
use craft\services\Fields;
use Illuminate\Support\Collection;
use yii\console\ExitCode;

/**
 * Manages custom fields.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class FieldsController extends Controller
{
    /**
     * Merges two custom fields together.
     *
     * @param string $handleA
     * @param string $handleB
     * @return int
     */
    public function actionMerge(string $handleA, string $handleB): int
    {
        if (!$this->interactive) {
            $this->stderr("The fields/merge command must be run interactively.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldsService = Craft::$app->getFields();

        $fieldA = $fieldsService->getFieldByHandle($handleA);
        if (!$fieldA) {
            $this->stderr("Invalid field handle: $handleA\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$fieldA instanceof MergeableFieldInterface) {
            $this->stderr(sprintf("%s fields don’t support merging.\n", $fieldA::displayName()), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldB = $fieldsService->getFieldByHandle($handleB);
        if (!$fieldB) {
            $this->stderr("Invalid field handle: $handleB\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$fieldB instanceof MergeableFieldInterface) {
            $this->stderr(sprintf("%s fields don’t support merging.\n", $fieldB::displayName()), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $layoutsA = $fieldsService->findFieldUsages($fieldA);
        $layoutsB = $fieldsService->findFieldUsages($fieldB);
        /** @var Collection<FieldLayout> $layouts */
        $layouts = Collection::make([...$layoutsA, ...$layoutsB])->unique();

        // Make sure all the layouts either have an ID or UUID; otherwise we wouldn't know what to do with it
        $unsavableLayouts = $layouts->filter(fn(FieldLayout $layout) => !$layout->id && !$layout->uid);
        if ($unsavableLayouts->isNotEmpty()) {
            $this->stdout(<<<EOD
These fields can’t be merged because one or both are used in a field layout(s)
that doesn’t have an `id` or `uid`:


EOD, Console::FG_RED);
            foreach ($unsavableLayouts as $layout) {
                $this->stdout(sprintf(" - %s\n", $this->layoutDescriptor($layout)), Console::FG_RED);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // If either one of them is a single-instance field, make sure there are no layouts that already include both
        if (!$fieldA::isMultiInstance() || !$fieldB::isMultiInstance()) {
            foreach ($layouts as $layout) {
                $layoutFields = Collection::make($layout->getCustomFields());
                if (
                    $layoutFields->contains(fn(FieldInterface $field) => $field->id === $fieldA->id) &&
                    $layoutFields->contains(fn(FieldInterface $field) => $field->id === $fieldB->id)
                ) {
                    $singleInstanceFields = array_filter([
                        !$fieldA::isMultiInstance() ? sprintf('%s (%s)', $fieldA->name, $fieldA::displayName()) : null,
                        !$fieldB::isMultiInstance() ? sprintf('%s (%s)', $fieldB->name, $fieldB::displayName()) : null,
                    ]);
                    $this->stdout($this->markdownToAnsi(sprintf(<<<EOD
These fields can’t be merged because %s %s support multiple instances,
and both fields are already in use by %s.
EOD,
                        implode(' and ', $singleInstanceFields),
                        count($singleInstanceFields) === 1 ? 'doesn’t' : 'don’t',
                        $this->layoutDescriptor($layout),
                    )), Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }

        $reasonA1 = $reasonA2 = $reasonB1 = $reasonB2 = null;
        $canMergeIntoFieldA = $fieldB->canMergeInto($fieldA, $reasonA1) && $fieldA->canMergeFrom($fieldB, $reasonA2);
        $canMergeIntoFieldB = $fieldA->canMergeInto($fieldB, $reasonB1) && $fieldB->canMergeFrom($fieldA, $reasonB2);

        if (!$canMergeIntoFieldA && !$canMergeIntoFieldB) {
            $reasons = array_filter([$reasonA1, $reasonA2, $reasonB1, $reasonB2]);
            $this->stderr(sprintf(
                "Neither of those fields support merging into/from the other one%s\n",
                !empty($reasons)
                    ? sprintf(":\n\n%s\n", implode("\n", array_map(fn(string $reason) => " - $reason", $reasons)))
                    : '.',
            ), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        [$persistingField, $outgoingField, $outgoingLayouts] = $this->choosePersistingField(
            $fieldA,
            $fieldB,
            $layoutsA,
            $layoutsB,
            $canMergeIntoFieldA,
            $canMergeIntoFieldB,
        );

        unset($fieldA, $fieldB);
        $this->stdout("\n");

        $this->do('Updating usages', function() use (
            $fieldsService,
            $persistingField,
            $outgoingField,
            $outgoingLayouts,
        ) {
            $projectConfigService = Craft::$app->getProjectConfig();
            $muteEvents = $projectConfigService->muteEvents;
            $projectConfigService->muteEvents = true;

            foreach ($outgoingLayouts as $layout) {
                $changed = false;
                foreach ($layout->getCustomFieldElements() as $layoutElement) {
                    if ($layoutElement->getFieldUid() === $outgoingField->uid) {
                        // hard code the label, handle, and instructions, if they differ from the persistent field
                        $layoutElement->label = $this->layoutElementOverride($persistingField->name, $outgoingField->name, $layoutElement->label);
                        $layoutElement->handle = $this->layoutElementOverride($persistingField->handle, $outgoingField->handle, $layoutElement->handle);
                        $layoutElement->instructions = $this->layoutElementOverride($persistingField->instructions, $outgoingField->instructions, $layoutElement->instructions);

                        $layoutElement->setField($persistingField);
                        $changed = true;
                    }
                }

                if ($changed) {
                    if (!$layout->id) {
                        // Maybe the ID just wasn't known
                        $layout->id = Db::idByUid(Table::FIELDLAYOUTS, $layout->uid);
                    }
                    if ($layout->id) {
                        $fieldsService->saveLayout($layout);
                    }
                    if ($layout->uid) {
                        $projectConfigOccurrences = $projectConfigService->find(fn(array $item) => isset($item[$layout->uid]));
                        foreach ($projectConfigOccurrences as $path => $item) {
                            $projectConfigService->set("$path.$layout->uid", $layout->getConfig());
                        }
                    }
                }
            }

            $projectConfigService->muteEvents = $muteEvents;
        });

        $this->do("Removing $outgoingField->name", function() use ($fieldsService, $outgoingField) {
            $fieldsService->deleteField($outgoingField);
        });

        $contentMigrator = Craft::$app->getContentMigrator();
        $migrationName = sprintf('m%s_merge_%s_into_%s', gmdate('ymd_His'), $outgoingField->handle, $persistingField->handle);
        $migrationPath = "$contentMigrator->migrationPath/$migrationName.php";

        $this->do("Generating content migration", function() use (
            $persistingField,
            $outgoingField,
            $migrationName,
            $migrationPath,
        ) {
            $content = $this->getView()->renderFile('@app/updates/field-merge.php.template', [
                'namespace' => Craft::$app->getContentMigrator()->migrationNamespace,
                'className' => $migrationName,
                'persistingFieldUid' => $persistingField->uid,
                'outgoingFieldUid' => $outgoingField->uid,
            ], $this);
            FileHelper::writeToFile($migrationPath, $content);
        });

        $this->stdout(" → Running content migration …\n");
        $contentMigrator->migrateUp($migrationName);

        $this->success(sprintf(<<<EOD
Fields merged. Commit `%s`
and your project config changes, and run `craft up` on other environments
for the changes to take effect.
EOD,
            FileHelper::relativePath($migrationPath)
        ));

        return ExitCode::OK;
    }

    /**
     * @param FieldInterface $fieldA
     * @param FieldInterface $fieldB
     * @param FieldLayout[] $layoutsA
     * @param FieldLayout[] $layoutsB
     * @param bool $canMergeIntoFieldA
     * @param bool $canMergeIntoFieldB
     * @return array{0:FieldInterface,1:FieldInterface,2:FieldLayout[]}
     */
    private function choosePersistingField(
        FieldInterface $fieldA,
        FieldInterface $fieldB,
        array $layoutsA,
        array $layoutsB,
        bool $canMergeIntoFieldA,
        bool $canMergeIntoFieldB,
    ): array {
        if ($canMergeIntoFieldA && $canMergeIntoFieldB) {
            $infoA = sprintf('%s %s', count($layoutsA), count($layoutsA) === 1 ? 'usage' : 'usages');
            $infoB = sprintf('%s %s', count($layoutsB), count($layoutsB) === 1 ? 'usage' : 'usages');

            $this->stdout("\n" . $this->markdownToAnsi(<<<MD
**Which field should persist?**

 - `$fieldA->handle` ($infoA)
 - `$fieldB->handle` ($infoB)
MD) . "\n\n");

            $choice = $this->select('Choose:', [
                $fieldA->handle => $fieldA->name,
                $fieldB->handle => $fieldB->name,
            ], count($layoutsA) >= count($layoutsB) ? $fieldA->handle : $fieldB->handle);

            return $choice === $fieldA->handle
                ? [$fieldA, $fieldB, $layoutsB]
                : [$fieldB, $fieldA, $layoutsA];
        }

        return $canMergeIntoFieldA
            ? [$fieldA, $fieldB, $layoutsB]
            : [$fieldB, $fieldA, $layoutsA];
    }

    private function layoutDescriptor(FieldLayout $layout): string
    {
        /** @var string|ElementInterface $elementType */
        $elementType = $layout->type;
        $elementDisplayName = $elementType::lowerDisplayName();
        $providerHandle = $layout->provider?->getHandle();
        return $providerHandle
            ? "the `$providerHandle` $elementDisplayName layout"
            : sprintf(
                "%s $elementDisplayName layout",
                in_array(strtolower($elementDisplayName[0]), ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a',
            );
    }

    private function layoutElementOverride(?string $persistingFieldValue, ?string $outgoingFieldValue, ?string $override): ?string
    {
        $persistingFieldValue = ($persistingFieldValue === '' ? null : $persistingFieldValue);
        $outgoingFieldValue = ($outgoingFieldValue === '' ? null : $outgoingFieldValue);
        $override = ($override === '' ? null : $override);
        $expected = $override ?? $outgoingFieldValue;
        return $persistingFieldValue !== $expected ? $expected : null;
    }
}
