<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\Support\Html;
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
            ->contentHtml(fn () => FormFields::elementSelectFieldHtml([
                'label' => t('Choose a new author'),
                'name' => 'newUserId',
                'elementType' => User::class,
                'criteria' => [
                    'id' => array_map(fn ($id) => "not $id", $oldUserIds),
                ],
                'single' => true,
            ]).
                implode('', array_map(fn ($id) => Html::hiddenInput('oldUserIds[]', $id), $oldUserIds)),
            )
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
