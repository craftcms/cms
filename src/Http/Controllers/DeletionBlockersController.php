<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\DeletionBlocker\BlockerRegistry;
use CraftCms\Cms\DeletionBlocker\SubjectTypeRegistry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Returns the blockers that should be addressed before something other than
 * elements is deleted in the control panel. `Craft.DeletionBlockersModal`
 * shows them.
 *
 * @since 6.0.0
 */
readonly class DeletionBlockersController
{
    public function __invoke(Request $request, SubjectTypeRegistry $subjectTypes, BlockerRegistry $blockerRegistry): JsonResponse
    {
        ['type' => $type, 'id' => $id] = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required'],
        ]);

        try {
            abort_if(
                $subjectTypes->requiresAdmin($type) && ! $request->craftUser()?->isAdmin(),
                403,
                'User is not permitted to perform this action.',
            );
            $subject = $subjectTypes->find($type, $id);
        } catch (InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }

        abort_if($subject === null, 400, "Invalid $type ID: $id");

        return new JsonResponse([
            'blockers' => $blockerRegistry->blockerData($blockerRegistry->blockers($type, $subject)),
            'label' => $subjectTypes->label($type, $subject),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }
}
