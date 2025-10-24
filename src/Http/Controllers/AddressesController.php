<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\elements\Address;
use craft\helpers\Cp;
use craft\web\Application;
use CraftCms\Cms\Addresses\Addresses;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class AddressesController
{
    use RespondsWithFlash;

    public function __construct(
        #[Give('Craft')]
        private Application $craft,
        private Fields $fields,
    ) {}

    public function fields(Request $request): Response
    {
        $request->validate([
            'namespace' => ['required', 'string'],
            'countryCode' => ['required', 'string'],
            'administrativeArea' => ['nullable', 'string'],
            'locality' => ['nullable', 'string'],
        ]);

        $address = new Address([
            'countryCode' => $request->get('countryCode'),
            'administrativeArea' => $request->get('administrativeArea'),
            'locality' => $request->get('locality'),
        ]);

        $html = $this->craft->getView()->namespaceInputs(
            fn () => Cp::addressFieldsHtml($address), $request->get('namespace')
        );

        return new JsonResponse([
            'fieldsHtml' => $html,
            'headHtml' => $this->craft->getView()->getHeadHtml(),
            'bodyHtml' => $this->craft->getView()->getBodyHtml(),
        ]);
    }

    public function saveFieldLayout(Addresses $addresses): Response
    {
        // Set the field layout
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

        if (! $addresses->saveFieldLayout($fieldLayout)) {
            return $this->asFailure(t('Couldn’t save address fields.'));
        }

        return $this->asSuccess(t('Address fields saved.'));
    }
}
