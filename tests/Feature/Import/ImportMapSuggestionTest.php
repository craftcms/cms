<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Tests\Support\ImportFixtures;

/**
 * Builds matrixOuter > withMatrix > matrixInner > withPlainText, and collects its destination
 * columns the way the mapping screen and ImportConfigController::nestedMappingCols() do — one
 * level per request, each nested level prefixed with its container's prefixedHandle.
 */
function nestedMatrixDestinationCols(): array
{
    $plainText = ImportFixtures::plainTextField('plainText', 'Plain Text');
    $plainText2 = ImportFixtures::plainTextField('plainText2', 'Plain Text 2');

    $withPlainText = ImportFixtures::entryTypeWithTitle([
        new CustomField(config: ['fieldUid' => $plainText->uid]),
        new CustomField(config: ['fieldUid' => $plainText2->uid]),
    ], ['handle' => 'withPlainText', 'name' => 'With Plain Text']);

    $matrixInner = ImportFixtures::matrixField('matrixInner', [$withPlainText], 'Matrix Inner');

    $withMatrix = ImportFixtures::entryTypeWithTitle([
        new CustomField(config: ['fieldUid' => $matrixInner->uid]),
    ], ['handle' => 'withMatrix', 'name' => 'With Matrix']);

    $matrixOuter = ImportFixtures::matrixField('matrixOuter', [$withMatrix], 'Matrix Outer');

    $layoutModel = FieldLayout::factory()->withContentTab([
        CustomField::make($plainText->handle),
        CustomField::make($matrixOuter->handle),
    ])->create();

    $fieldsService = app(FieldsService::class);

    $colsFor = function (string $handle, string $prefix) use ($fieldsService): array {
        $field = $fieldsService->getFieldByHandle($handle);
        $cols = [];

        foreach ($field->getFieldLayoutProviders() as $provider) {
            $cols = array_merge($cols, ImportHelper::getDestinationColsForFieldLayout(
                $provider->getFieldLayout(),
                $field,
                $provider,
                $prefix,
            ));
        }

        return $cols;
    };

    $top = ImportHelper::getDestinationColsForFieldLayout($fieldsService->getLayoutByUid($layoutModel->uid));
    $level1 = $colsFor('matrixOuter', 'matrixOuter');
    $innerCol = collect($level1)->firstWhere('handle', 'matrixInner');
    $level2 = $colsFor('matrixInner', $innerCol['prefixedHandle']);

    return [...$top, ...$level1, ...$level2];
}

/** The column list a nested-matrix export produces: it carries `fields`, never an entry type. */
function nestedMatrixSourceCols(): array
{
    return array_map(fn (string $value) => ['label' => $value, 'value' => $value], [
        '',
        'authorIds',
        'matrixOuter',
        'matrixOuter.fields',
        'matrixOuter.fields.matrixInner',
        'matrixOuter.fields.matrixInner.fields',
        'matrixOuter.fields.matrixInner.fields.plainText',
        'matrixOuter.fields.matrixInner.fields.plainText2',
        'matrixOuter.fields.matrixInner.matchCriteria',
        'matrixOuter.fields.matrixInner.matchCriteria.title',
        'matrixOuter.fields.matrixInner.title',
        'matrixOuter.fields.matrixInner.type',
        'matrixOuter.matchCriteria',
        'matrixOuter.matchCriteria.title',
        'matrixOuter.title',
        'matrixOuter.type',
        'plainText',
        'sectionId',
        'title',
        'typeId',
    ]);
}

it('suggests a column for every level of a real nested matrix', function () {
    $result = ImportHelper::suggestMapValues(nestedMatrixDestinationCols(), nestedMatrixSourceCols(), []);

    expect($result)->toEqual([
        'plainText' => 'plainText',
        'matrixOuter' => [
            'withMatrix' => [
                'title' => 'matrixOuter.title',
                'fields' => [
                    'matrixInner' => [
                        'withPlainText' => [
                            'title' => 'matrixOuter.fields.matrixInner.title',
                            'fields' => [
                                'plainText' => 'matrixOuter.fields.matrixInner.fields.plainText',
                                'plainText2' => 'matrixOuter.fields.matrixInner.fields.plainText2',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

it('suggests the same columns however their handles are spelled', function () {
    $sourceCols = array_map(fn (string $value) => ['label' => $value, 'value' => $value], [
        'matrixouter.fields.matrix-inner.fields.plain-text',
        'MATRIXOUTER.fields.matrix inner.fields.Plain Text 2',
    ]);

    $result = ImportHelper::suggestMapValues(nestedMatrixDestinationCols(), $sourceCols, []);

    $innerFields = $result['matrixOuter']['withMatrix']['fields']['matrixInner']['withPlainText']['fields'];

    expect($innerFields)->toBe([
        'plainText' => 'matrixouter.fields.matrix-inner.fields.plain-text',
        'plainText2' => 'MATRIXOUTER.fields.matrix inner.fields.Plain Text 2',
    ]);
});
