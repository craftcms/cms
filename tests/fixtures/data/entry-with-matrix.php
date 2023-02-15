<?php

$relatedEntryQuery = \craft\elements\Entry::find()->title('Theories of life');

return [
    [
        'authorId' => '1',
        'sectionId' => '1006',
        'typeId' => '1006',
        'title' => 'Matrix with relational field',
        'fieldLayoutType' => 'field_layout_with_matrix_with_relational_field',
        'plainTextField2' => "You think it's code you're testing now?",
        'matrixSecond' => [
            'new1' => [
                'type' => 'bBlock',
                'fields' => [
                    'entriesSubfield' => $relatedEntryQuery,
                ],
            ],
            'new2' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text',

                ],
            ],
        ],
        'relatedEntry' => $relatedEntryQuery,
    ],
    [
        'authorId' => '1',
        'sectionId' => '1006',
        'typeId' => '1006',
        'title' => 'Child in structure',
        'fieldLayoutType' => 'field_layout_with_matrix_with_relational_field',
        'plainTextField2' => "You think it's code you're testing now?",
        'matrixSecond' => [
            'new1' => [
                'type' => 'bBlock',
                'fields' => [
                    'entriesSubfield' => $relatedEntryQuery,
                ],
            ],
            'new2' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text',

                ],
            ],
        ],
        'relatedEntry' => $relatedEntryQuery,
    ],
];
