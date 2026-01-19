<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class EditionController
{
    use RespondsWithFlash;

    public function tryEdition(Request $request): JsonResponse
    {
        $request->validate([
            'edition' => ['required', 'string'],
        ]);

        $editionHandle = $request->input('edition');
        $licensedEdition = Edition::getLicensed() ?? Edition::Solo;

        try {
            $edition = Edition::fromHandle($editionHandle);
        } catch (InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }

        if ($edition->value > $licensedEdition->value && ! Edition::canTest()) {
            abort(400, 'Craft is not permitted to test edition upgrades from this server');
        }

        Edition::set($edition);

        return $this->asJsonSuccess();
    }

    public function switchToLicensedEdition(): JsonResponse
    {
        if (Edition::isWrong()) {
            Edition::set(Edition::getLicensed());
        }

        return $this->asJsonSuccess();
    }
}
