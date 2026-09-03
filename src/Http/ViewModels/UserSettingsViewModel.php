<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Heading;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserSettingsController;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Data\UserSettings;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserGroups;

use function CraftCms\Cms\t;

class UserSettingsViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly UserSettings $settings,
        private readonly Volumes $volumes,
        private readonly UserGroups $userGroups,
        private readonly FormResolver $formResolver,
        private readonly bool $canRequire2fa,
        private readonly bool $canManagePublicRegistration,
        private readonly bool $readOnly,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
    {
        $values = $this->values ?? $this->initialValues();
        $form = Form::make([
            Heading::make('user-photos-heading', t('User Photos')),
            Heading::make('user-photo-location-heading', t('User Photo Location'))
                ->level(3)
                ->description(t('Where do you want to store user photos? Note that the subfolder path can contain variables like {username}.')),
            Field::make(
                t('User Photo Volume'),
                Combobox::make('photoVolumeUid')
                    ->options($this->photoVolumeOptions())
                    ->requireOptionMatch()
                    ->showAllOnEmpty()
                    ->clearable(),
            )->width(33),
            Field::make(
                t('Subpath'),
                Text::make('photoSubpath')
                    ->placeholder(t('path/to/subfolder'))
                    ->dir('ltr')
                    ->textExpanderTriggers(SelectOptions::getObjectTemplateTextExpanderTriggers(User::class)),
            )
                ->tip(t('Type `{` to choose a user property.'))
                ->width(66),
        ]);

        if ($this->canRequire2fa) {
            $form->add(
                Separator::make('security-separator'),
                Heading::make('security-heading', t('Security')),
                Field::make(
                    t('Require two-step verification'),
                    Choice::make('require2fa')->options($this->require2faOptions())->multiple(),
                )->instructions(t('Choose which users must use two-step verification to sign in.')),
            );
        }

        if ($this->canManagePublicRegistration) {
            $form->add(
                Field::make(
                    t('Verify email addresses'),
                    Lightswitch::make('requireEmailVerification'),
                )->instructions(t('Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)')),
                Separator::make('public-registration-separator'),
                Heading::make('public-registration-heading', t('Public Registration')),
                Field::make(
                    t('Allow public registration'),
                    Lightswitch::make('allowPublicRegistration'),
                )->reactive(),
            );

            if ($values['allowPublicRegistration']) {
                $form->add(
                    Field::make(
                        t('Validate custom fields on public registration'),
                        Lightswitch::make('validateOnPublicRegistration'),
                    )->instructions(t('Whether custom fields should be validated during public registration.')),
                    Field::make(
                        t('Deactivate users by default'),
                        Lightswitch::make('deactivateByDefault'),
                    )->instructions(t('Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.')),
                    Field::make(
                        t('Default User Group'),
                        Choice::make('defaultGroup')->options($this->defaultGroupOptions()),
                    )->instructions(t('Choose a user group that publicly-registered members will be added to by default.')),
                );
            } else {
                $form->add(
                    HiddenField::make('validateOnPublicRegistration'),
                    HiddenField::make('deactivateByDefault'),
                    HiddenField::make('defaultGroup'),
                );
            }
        }

        return $this->formResolver->resolve($form, new FormContext(
            values: $values,
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly && $this->canManagePublicRegistration,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([UserSettingsController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly || ! $this->canManagePublicRegistration
            ? null
            : action([UserSettingsController::class, 'renderForm']);
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        return [
            'photoVolumeUid' => $this->settings->photoVolumeUid ?? '',
            'photoSubpath' => $this->settings->photoSubpath ?? '',
            'require2fa' => $this->settings->require2fa,
            'requireEmailVerification' => $this->settings->requireEmailVerification,
            'allowPublicRegistration' => $this->settings->allowPublicRegistration,
            'validateOnPublicRegistration' => $this->settings->validateOnPublicRegistration,
            'deactivateByDefault' => $this->settings->deactivateByDefault,
            'defaultGroup' => $this->settings->defaultGroup ?? '',
        ];
    }

    /** @return list<array{label: string, value: string, data?: array{addOption: bool}}> */
    private function photoVolumeOptions(): array
    {
        return $this->volumes->getAllVolumes()
            ->map(fn (Volume $volume): array => [
                'label' => $volume->name,
                'value' => (string) $volume->uid,
            ])
            ->sortBy('label')
            ->values()
            ->push([
                'label' => t('Create a new volume…'),
                'value' => '__createVolume__',
                'data' => ['addOption' => true],
            ])
            ->all();
    }

    /** @return list<array{label: string, value: string}> */
    private function require2faOptions(): array
    {
        return [
            ['label' => t('All users'), 'value' => 'all'],
            ['label' => t('Admins'), 'value' => 'admins'],
            ...$this->userGroupOptions(),
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private function defaultGroupOptions(): array
    {
        return [
            ['label' => t('None'), 'value' => ''],
            ...$this->userGroupOptions(),
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private function userGroupOptions(): array
    {
        return $this->userGroups->getAllGroups()
            ->map(fn (UserGroup $group): array => [
                'label' => t($group->name ?? '', category: 'site'),
                'value' => (string) $group->uid,
            ])
            ->values()
            ->all();
    }
}
