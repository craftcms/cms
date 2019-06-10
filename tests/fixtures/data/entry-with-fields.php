<?php

return [
    [
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Theories of matrix',
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'plainTextField' => "You think it's code you're testing now?",
        'matrixFirst' => [
            'new1' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text'

                ],
            ],
            'new2' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text'
                ],
            ],
        ],
    ],
];
