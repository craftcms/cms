<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\helpers\Cp;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class AddressesController
{
    use RespondsWithFlash;

    public function __construct(
        private Fields $fields,
    ) {}

    public function fields(Request $request, HtmlStack $HtmlStack): Response
    {
        $request->validate([
            'namespace' => ['required', 'string'],
            'countryCode' => ['required', 'string'],
            'administrativeArea' => ['nullable', 'string'],
            'locality' => ['nullable', 'string'],
        ]);

        $address = new Address([
            'countryCode' => $request->input('countryCode'),
            'administrativeArea' => $request->input('administrativeArea'),
            'locality' => $request->input('locality'),
        ]);

        $html = InputNamespace::namespaceInputs(
            fn () => Cp::addressFieldsHtml($address), $request->input('namespace')
        );

        return new JsonResponse([
            'fieldsHtml' => $html,
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
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
