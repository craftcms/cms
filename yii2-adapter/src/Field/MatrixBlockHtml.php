<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Events\MatrixBlockHtmlRendering;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class MatrixBlockHtml
{
    public function handle(MatrixBlockHtmlRendering $event): void
    {
        $html = '';
        $field = $event->field;
        if ($field !== null && $event->entries !== []) {
            $owner = $event->entries[0]->getOwner();
            $entryTypes = $field->getEntryTypesForOwner($owner);

            foreach ($event->entries as $entry) {
                $html .= InputNamespace::namespaceInputs(fn(): string => template('_components/fieldtypes/Matrix/block', [
                    'name' => $field->handle,
                    'entryTypes' => $entryTypes,
                    'entry' => $entry,
                    'isFresh' => $event->fresh,
                    'staticEntries' => $event->staticEntries,
                    ...$this->formVariables($field, $entry),
                ]), $event->namespace);
            }
        }

        $event->response = new JsonResponse([
            'blockHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    /** @return array{formPayload: array<string, mixed>, siteName: string|null} */
    private function formVariables(Matrix $field, Entry $entry): array
    {
        $namespace = InputNamespace::namespaceInputName("{$field->handle}[entries][uid:{$entry->uid}]");
        $payload = app(FieldLayoutCompiler::class)->compile(
            $entry->getFieldLayout(),
            $entry,
            new FormContext(
                namespace: explode('[', str_replace(']', '', $namespace)),
                errors: $entry->errors()->getMessages(),
            ),
        );

        return [
            'formPayload' => $payload->jsonSerialize(),
            'siteName' => count($field->getSupportedSitesForElement($entry)) > 1
                ? t($entry->getOwner()->getSite()->getName(), category: 'site')
                : null,
        ];
    }
}
