<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Commands\Concerns;

use craft\models\FieldLayout;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * @internal
 */
trait MergesFields
{
    /**
     * @param  Collection<FieldLayout>  $outgoingLayouts
     * @param  string[]  $migrationPaths
     */
    protected function mergeFields(
        Fields $fieldsService,
        FieldInterface $persistingField,
        FieldInterface $outgoingField,
        Collection|array $outgoingLayouts,
        array &$migrationPaths,
    ): void {
        $this->components->task("Updating usages for `$outgoingField->handle`", function () use (
            $fieldsService,
            $persistingField,
            $outgoingField,
            $outgoingLayouts,
        ) {
            $projectConfigService = app(ProjectConfig::class);
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

                if (! $changed) {
                    continue;
                }

                // Maybe the ID just wasn't known
                $layout->id ??= DB::table(Table::FIELDLAYOUTS)->idByUid($layout->uid);

                if ($layout->id) {
                    $fieldsService->saveLayout($layout);
                }

                if ($layout->uid) {
                    $projectConfigOccurrences = $projectConfigService->find(fn (array $item) => isset($item[$layout->uid]));

                    foreach ($projectConfigOccurrences as $path => $item) {
                        $projectConfigService->set("$path.$layout->uid", $layout->getConfig());
                    }
                }
            }

            $projectConfigService->muteEvents = $muteEvents;
        });

        $this->components->task("Removing `$outgoingField->handle`", function () use ($fieldsService, $outgoingField) {
            $fieldsService->deleteField($outgoingField);
        });

        $migrationName = sprintf('%s_merge_%s_into_%s', gmdate('Y_m_d_His'), $outgoingField->handle, $persistingField->handle);
        $migrationPath = database_path("migrations/{$migrationName}.php");

        $this->components->task("Generating content migration for `$outgoingField->handle`", function () use (
            &$migrationPaths,
            $persistingField,
            $outgoingField,
            $migrationPath,
        ) {
            ob_start();
            File::getRequire(Aliases::get('@craftcms/stubs/field-merge.php.stub'), [
                'persistingFieldUid' => $persistingField->uid,
                'outgoingFieldUid' => $outgoingField->uid,
            ]);
            $content = ob_get_clean();

            File::put($migrationPath, $content);

            $migrationPaths[] = $migrationPath;
        });

        $this->components->task(" → Running content migration for `$outgoingField->handle` …", function () {
            app(Migrator::class)->track('content')->run();
        });
    }

    protected function layoutElementOverride(?string $persistingFieldValue, ?string $outgoingFieldValue, ?string $override): ?string
    {
        $persistingFieldValue = ($persistingFieldValue === '' ? null : $persistingFieldValue);
        $outgoingFieldValue = ($outgoingFieldValue === '' ? null : $outgoingFieldValue);
        $override = ($override === '' ? null : $override);
        $expected = $override ?? $outgoingFieldValue;

        return $persistingFieldValue !== $expected ? $expected : null;
    }
}
