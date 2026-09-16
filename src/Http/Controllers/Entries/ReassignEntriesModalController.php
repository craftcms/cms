<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class ReassignEntriesModalController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function show(Request $request): CpModalResponse
    {
        $this->requirePermission('deleteUsers');

        $oldUserIds = $request->validate([
            'oldUserIds' => ['required', 'array'],
            'oldUserIds.*' => ['integer'],
        ])['oldUserIds'];

        return new CpModalResponse()
            ->action('entries/reassign')
            ->form(Form::make([
                Field::make(t('Choose a new author'), ElementSelect::make('newUserId')
                    ->elementType(User::class)
                    ->criteria(['id' => ['not', ...$oldUserIds]])
                    ->single()),
                ...array_map(fn ($index) => HiddenField::make(['oldUserIds', (string) $index]), array_keys($oldUserIds)),
            ]), ['oldUserIds' => $oldUserIds])
            ->submitButtonLabel(t('Reassign'));
    }

    public function store(Request $request, Entries $entries): Response
    {
        $this->requirePermission('deleteUsers');

        $request->validate([
            'oldUserIds' => ['required', 'array'],
            'oldUserIds.*' => ['integer'],
            'newUserId' => ['required', 'integer'],
        ]);

        $oldUserIds = array_map(fn ($id) => (int) $id, $request->array('oldUserIds'));
        $newUserId = $request->integer('newUserId');

        if (! $newUserId) {
            return $this->asFailure(t('No new author selected.'));
        }

        $count = $entries->reassignEntries($oldUserIds, $newUserId);

        return $this->asSuccess(t('{type} reassigned.', [
            'type' => $count === 1 ? Entry::displayName() : Entry::pluralDisplayName(),
        ]));
    }
}
