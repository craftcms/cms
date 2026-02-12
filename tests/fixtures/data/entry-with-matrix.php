<?php

$relatedEntryQuery = \craft\elements\Entry::find()->title('Theories of life');

return [
    [
        'authorId' => '1',
        'sectionId' => '1006',
        'typeId' => '1006',
        'title' => 'Matrix with relational field',
        'fieldLayoutUid' => 'field-layout-1003----------------uid',
        'plainTextField2' => "You think it's code you're testing now?",
        'matrixSecond' => [
            'new1' => [
                'type' => 'matrixLayout3',
                'fields' => [
                    'entriesSubfield' => $relatedEntryQuery,
                ],
            ],
            'new2' => [
                'type' => 'matrixLayout2',
                'fields' => [
                    'secondSubfield' => 'Some text',
                ],
            ],
        ],
        'relatedEntry' => $relatedEntryQuery,
    ],
];
