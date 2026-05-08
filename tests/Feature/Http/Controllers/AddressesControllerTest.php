<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\LabelField;
use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('can list fields with a namespace and countryCode', function () {
    postJson(action([AddressesController::class, 'fields'], [
        'namespace' => 'test',
        'countryCode' => 'US',
    ]))->assertOk()->assertJsonStructure([
        'fieldsHtml',
        'headHtml',
        'bodyHtml',
    ]);
});

it('can save a field layout', function () {
    postJson(action([AddressesController::class, 'saveFieldLayout']), [
        'fieldLayout' => json_encode([
            'uid' => Str::uuid()->toString(),
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => LabelField::class,
                        ],
                    ],
                ],
            ],
        ]),
    ])->assertOk()->assertJson([
        'message' => 'Address fields saved.',
    ]);
});
