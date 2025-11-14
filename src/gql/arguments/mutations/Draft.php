<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\mutations;

use GraphQL\Type\Definition\Type;

/**
 * Class Draft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Draft extends Entry
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        $parentArguments = parent::getArguments();
        unset($parentArguments['id'], $parentArguments['uid']);

        return array_merge($parentArguments, [
            'draftId' => [
                'name' => 'draftId',
                'type' => Type::id(),
                'description' => 'The ID of the draft. Can only be omitted if creating an unpublished draft',
            ],
            'provisional' => [
                'name' => 'provisional',
                'type' => Type::boolean(),
                'description' => 'Whether a provisional draft should be looked up.',
            ],
            'asUnpublishedDraft' => [
                'name' => 'asUnpublishedDraft',
                'type' => Type::boolean(),
                'description' => 'Whether an unpublished draft should be created.',
            ],
            'draftName' => [
                'name' => 'draftName',
                'type' => Type::string(),
                'description' => 'The name of the draft.',
            ],
            'draftNotes' => [
                'name' => 'draftNotes',
                'type' => Type::string(),
                'description' => 'Notes for the draft.',
            ],
        ]);
    }
}
