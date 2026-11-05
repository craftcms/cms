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

/**
 * @since 6.0.0
 */
readonly class AddressesController
{
    public function __construct(private Addresses $addresses) {}

    /**
     * Returns the address fields for a country and its subdivisions.
     *
     * The address has no owner here, so the request says whether it’s the current user’s own,
     * which gives its fields `autocomplete` tokens.
     */
    public function fields(Request $request, HtmlStack $HtmlStack): Response
    {
        $request->validate([
            'namespace' => ['required', 'string'],
            'countryCode' => ['required', 'string'],
            'administrativeArea' => ['nullable', 'string'],
            'locality' => ['nullable', 'string'],
            'belongsToCurrentUser' => ['nullable', 'boolean'],
        ]);

        $address = new Address([
            'countryCode' => $request->input('countryCode'),
            'administrativeArea' => $request->input('administrativeArea'),
            'locality' => $request->input('locality'),
        ]);

        $belongsToCurrentUser = $request->boolean('belongsToCurrentUser');
        $html = InputNamespace::namespaceInputs(
            fn () => FormFields::addressFieldsHtml($address, belongsToCurrentUser: $belongsToCurrentUser),
            $request->input('namespace')
        );

        return new JsonResponse([
            'fieldsHtml' => $html,
            'fieldDefinitions' => $this->addresses->getFormFieldDefinitions($address, $belongsToCurrentUser),
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
        ]);
    }
}
