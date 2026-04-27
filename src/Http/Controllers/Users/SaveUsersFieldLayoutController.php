<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;

use function CraftCms\Cms\t;

readonly class SaveUsersFieldLayoutController
{
    use RespondsWithFlash;

    public function __invoke(Fields $fields, Users $users)
    {
        $fieldLayout = $fields->assembleLayoutFromPost();
        $fieldLayout->type = User::class;
        $fieldLayout->reservedFieldHandles = [
            'active',
            'addresses',
            'admin',
            'affiliatedSiteId',
            'email',
            'firstName',
            'friendlyName',
            'fullName',
            'groups',
            'lastName',
            'locked',
            'name',
            'password',
            'pending',
            'photo',
            'suspended',
            'username',
        ];

        if (! $users->saveLayout($fieldLayout)) {
            return $this->asFailure(t('Couldn’t save user fields.'), [
                'fieldLayout' => $fieldLayout,
            ]);
        }

        return $this->asSuccess(t('User fields saved.'));
    }
}
