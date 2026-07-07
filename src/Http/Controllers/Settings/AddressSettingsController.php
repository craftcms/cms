<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class AddressSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly Addresses $addresses,
        private readonly Fields $fields,
        private readonly FieldLayoutDesigner $fieldLayoutDesigner,
    ) {}

    public function index(): CpScreenResponse
    {
        $fieldLayout = $this->addresses->getFieldLayout();

        return new CpScreenResponse()
            ->title(t('Address Fields'))
            ->addCrumb(t('Settings'), 'settings')
            ->inertiaPage('settings/addresses/Fields', [
                'fieldLayoutDesigner' => [
                    'html' => $this->fieldLayoutDesigner->fieldHtml($fieldLayout, [
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
        $fieldLayout->type = Address::class;
        $fieldLayout->reservedFieldHandles = [
            'address',
            'countryCode',
            'fullName',
            'latLong',
            'organization',
            'organizationTaxId',
        ];

        if (! $this->addresses->saveFieldLayout($fieldLayout)) {
            return $this->asFailure(t('Couldn’t save address fields.'), [
                'errors' => $fieldLayout->errors()->getMessages(),
            ]);
        }

        return $this->asSuccess(t('Address fields saved.'));
    }
}
