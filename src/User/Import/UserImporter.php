<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Import;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use CraftCms\Cms\User\Events\UserGroupsAndPermissionsAssigning;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Imports data into User elements.
 *
 * @since 6.0.0
 */
class UserImporter extends ElementImporter
{
    /**
     * The sensitive user attributes that can be imported. They don’t grant any privileges.
     */
    private const array IMPORTABLE_SENSITIVE_ATTRIBUTES = [
        'affiliatedSiteId',
        'email',
        'passwordResetRequired',
        'photoId',
    ];

    #[Override]
    public static function targetClass(): string
    {
        return User::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Users');
    }

    /**
     * Users have one field layout for the element type, so there's nothing for the step to
     * choose — resolve it as soon as the importer is built.
     */
    #[Override]
    public function resolveDefaultFieldLayout(): void
    {
        $fieldLayout = Fields::getLayoutByType(User::class, false);
        $this->fieldLayout($fieldLayout ? $fieldLayout->uid : User::class);
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes, array $data): void
    {
        $photoSource = null;
        // a photo given as a URL or a file path gets turned into an asset once the user is saved
        if (
            isset($attributes['photoId']) &&
            is_string($attributes['photoId']) &&
            trim($attributes['photoId']) !== '' &&
            ! is_numeric($attributes['photoId'])
        ) {
            $photoSource = $attributes['photoId'];
            unset($attributes['photoId']);
        }

        // sensitive user attributes (e.g. admin) can't be imported, apart from the ones the importer offers
        parent::setAttributesForImport(
            $element,
            Arr::except($attributes, array_diff(User::SENSITIVE_ATTRIBUTES, self::IMPORTABLE_SENSITIVE_ATTRIBUTES)),
            $data,
        );

        // deal with user groups & photo
        $this->afterItemImported(function (mixed $user) use ($data, $photoSource) {
            if (! $user instanceof User) {
                return;
            }

            if ($photoSource) {
                $this->importPhotoFromFile($user, trim($photoSource));
            }

            if (Edition::isAtLeast(Edition::Pro)) {
                event(new UserGroupsAndPermissionsAssigning($user));

                if (array_key_exists('groups', $data)) {
                    $groups = $this->prepareUserGroups($data['groups']);

                    if (! Users::assignUserToGroups($user->id, Arr::pluck($groups, 'id'))) {
                        ImportLog::warning('Couldn’t assign user to groups', [
                            'user' => $user->id,
                            'groups' => $data['groups'],
                        ]);
                    }
                    $user->setGroups($groups);
                }

                event(new GroupsAndPermissionsAssigned($user));
            }
        });
    }

    #[Override]
    protected function isPropertyAvailable(array $prop): bool
    {
        return match ($prop['name']) {
            'groups' => Edition::isAtLeast(Edition::Pro),
            'affiliatedSiteId' => Sites::isMultiSite(),
            'photoId' => ! empty(ProjectConfig::get('users.photoVolumeUid')),
            default => parent::isPropertyAvailable($prop),
        };
    }

    /**
     * Saves an imported file (an absolute URL or a local path) as the user’s photo, logging any failure.
     */
    private function importPhotoFromFile(User $user, string $source): void
    {
        $filename = AssetsHelper::importFilename($source);
        $tempPath = null;

        try {
            [$tempPath, $extension] = AssetsHelper::fetchImportFile(static::urlValidator(), $source, $filename, ! self::isRemoteSource($this->source));

            // without an extension, the fetched file's type decides it, and the temp file needs it too to be saved as a photo
            if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                $filename .= ".$extension";
                $typedTempPath = sprintf('%s/%s.%s', pathinfo($tempPath, PATHINFO_DIRNAME), pathinfo($tempPath, PATHINFO_FILENAME), $extension);
                File::move($tempPath, $typedTempPath);
                $tempPath = $typedTempPath;
            }

            Users::saveUserPhoto($tempPath, $user, $filename);
        } catch (Throwable $e) {
            if ($tempPath !== null) {
                File::delete($tempPath);
            }

            ImportLog::warning("Couldn’t import “{$source}” as the user’s photo: ".$e->getMessage(), [
                'user' => $user->id,
            ]);
        }
    }

    /**
     * @param  string|array<mixed>|null  $groupsData
     * @return UserGroup[]
     */
    private function prepareUserGroups(null|string|array $groupsData = null): array
    {
        if (empty($groupsData)) {
            return [];
        }

        $incomingGroups = [];

        // normalize incoming groups
        if (is_array($groupsData)) {
            foreach ($groupsData as $item) {
                $incomingGroups[] = UserGroups::resolveGroup($item);
            }
        } else {
            $incomingGroups[] = UserGroups::resolveGroup($groupsData);
        }

        return array_unique(array_filter($incomingGroups));
    }
}
