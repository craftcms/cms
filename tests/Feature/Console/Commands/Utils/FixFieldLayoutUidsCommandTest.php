<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;

it('reports when no duplicate field layout uids exist', function () {
    $this->artisan('craft:utils:fix-field-layout-uids')
        ->expectsOutputToContain('No duplicate UUIDs were found.')
        ->assertSuccessful();
});

it('fixes duplicate and missing field layout uids', function () {
    $fieldLayoutUid = (string) Str::uuid();
    $sharedTabUid = (string) Str::uuid();
    $sharedElementUid = (string) Str::uuid();

    ProjectConfig::set('testFixture.first', [
        'fieldLayouts' => [
            $fieldLayoutUid => [
                'tabs' => [
                    [
                        'uid' => $sharedTabUid,
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => $sharedElementUid,
                                'type' => CustomField::class,
                            ],
                            [
                                'type' => CustomField::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    ProjectConfig::set('testFixture.second', [
        'fieldLayouts' => [
            $fieldLayoutUid => [
                'tabs' => [
                    [
                        'uid' => $sharedTabUid,
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => $sharedElementUid,
                                'type' => CustomField::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    ProjectConfig::set('testFixture.solo', [
        'fieldLayout' => [
            'tabs' => [
                [
                    'name' => 'Content',
                    'elements' => [
                        [
                            'uid' => $sharedElementUid,
                            'type' => CustomField::class,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    ProjectConfig::regenerateExternalConfig();

    $this->artisan('utils/fix-field-layout-uids')
        ->expectsOutputToContain('Looking for duplicate UUIDs ...')
        ->expectsOutputToContain("Missing UUID found at testFixture.first.fieldLayouts.$fieldLayoutUid.tabs.0.elements.1")
        ->expectsOutputToContain("Duplicate UUID at testFixture.second.fieldLayouts.$fieldLayoutUid.tabs.0")
        ->expectsOutputToContain("Duplicate UUID at testFixture.second.fieldLayouts.$fieldLayoutUid")
        ->expectsOutputToContain('Fixed 6 duplicate or missing UUIDs')
        ->assertSuccessful();

    $firstConfig = ProjectConfig::get('testFixture.first');
    $secondConfig = ProjectConfig::get('testFixture.second');
    $soloConfig = ProjectConfig::get('testFixture.solo');

    $firstFieldLayouts = ProjectConfigHelper::unpackAssociativeArray($firstConfig['fieldLayouts']);
    $secondFieldLayouts = ProjectConfigHelper::unpackAssociativeArray($secondConfig['fieldLayouts']);

    $firstLayout = $firstFieldLayouts[$fieldLayoutUid];
    $secondFieldLayoutUid = array_key_first($secondFieldLayouts);
    $secondLayout = $secondFieldLayouts[$secondFieldLayoutUid];
    $soloLayout = $soloConfig['fieldLayout'];

    expect($secondFieldLayoutUid)->not->toBe($fieldLayoutUid)
        ->and($firstLayout['tabs'][0]['uid'])->toBe($sharedTabUid)
        ->and($firstLayout['tabs'][0]['elements'][0]['uid'])->toBe($sharedElementUid)
        ->and($firstLayout['tabs'][0]['elements'][1]['uid'])->not->toBeEmpty()
        ->and($firstLayout['tabs'][0]['elements'][1]['uid'])->not->toBe($sharedElementUid)
        ->and($secondLayout['tabs'][0]['uid'])->not->toBe($sharedTabUid)
        ->and($secondLayout['tabs'][0]['elements'][0]['uid'])->not->toBe($sharedElementUid)
        ->and($soloLayout['tabs'][0]['uid'])->not->toBeEmpty()
        ->and($soloLayout['tabs'][0]['elements'][0]['uid'])->not->toBe($sharedElementUid);

    $allUids = [
        $firstLayout['tabs'][0]['uid'],
        $firstLayout['tabs'][0]['elements'][0]['uid'],
        $firstLayout['tabs'][0]['elements'][1]['uid'],
        $secondLayout['tabs'][0]['uid'],
        $secondLayout['tabs'][0]['elements'][0]['uid'],
        $soloLayout['tabs'][0]['uid'],
        $soloLayout['tabs'][0]['elements'][0]['uid'],
    ];

    expect(array_unique($allUids))->toHaveCount(count($allUids));
});
