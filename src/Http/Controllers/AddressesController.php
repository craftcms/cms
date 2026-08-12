<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AddressesController
{
    public function __construct(private Addresses $addresses) {}

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
            fn () => FormFields::addressFieldsHtml($address), $request->input('namespace')
        );

        return new JsonResponse([
            'fieldsHtml' => $html,
            'fieldDefinitions' => $this->addresses->getFormFieldDefinitions($address),
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
        ]);
    }
}
