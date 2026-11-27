<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\ElementIndexViewStates;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Stores the element index view states the browser keeps in local storage
 * for the session, so index page loads can render their rows (see
 * {@see ElementIndexViewStates}).
 *
 * @since 6.0.0
 */
readonly class ElementIndexViewStateController
{
    public function __invoke(Request $request, ElementIndexViewStates $viewStates): Response
    {
        $request->validate([
            'states' => ['present', 'array', 'max:500'],
            'states.*' => ['array'],
            'all' => ['boolean'],
        ]);

        /** @var array<string, array<string, mixed>> $states */
        $states = $request->array('states');

        foreach (array_keys($states) as $key) {
            if (! Str::isMatch('/^elementindex\.[\w\\\\]+\.index$/', (string) $key)) {
                throw ValidationException::withMessages(['states' => "Invalid view state key: $key"]);
            }
        }

        $viewStates->put($states, $request->boolean('all'));

        return response()->noContent();
    }
}
