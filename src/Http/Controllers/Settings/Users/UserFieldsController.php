<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class UserFieldsController extends BaseUserSettingsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly Fields $fields,
        private readonly Users $users,
        private readonly FieldLayoutDesigner $fieldLayoutDesigner,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): CpScreenResponse
    {
        $fieldLayout = $this->fields->getLayoutByType(User::class);

        return new CpScreenResponse()
            ->title(t('User Settings'))
            ->crumbs($this->crumbs(t('User Profile Fields')))
            ->inertiaPage('settings/users/Fields', [
                'fieldLayoutDesigner' => [
                    'html' => $this->fieldLayoutDesigner->fieldHtml($fieldLayout, [
                        'disabled' => $this->readOnly,
                        'withGeneratedFields' => true,
                        'withCardViewDesigner' => true,
                        'autoBoot' => false,
                    ]),
                ],
            ]);
    }

    public function store(): Response
    {
        $fieldLayout = $this->fields->assembleLayoutFromPost();
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

        if (! $this->users->saveLayout($fieldLayout)) {
            return $this->asFailure(t('Couldn’t save user fields.'), [
                'errors' => $fieldLayout->errors()->getMessages(),
            ]);
        }

        return $this->asSuccess(t('User fields saved.'));
    }
}
