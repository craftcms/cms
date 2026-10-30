<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Guidance\Guidance;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AiGuidance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * Saves the AI assistant’s guidance from the AI Guidance utility: for the sites the user can edit, and for every site if
 * they can edit every site.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class GuidanceController
{
    use RespondsWithFlash;

    public function store(Request $request, Utilities $utilities, Guidance $guidance): Response
    {
        abort_unless($utilities->checkAuthorization(AiGuidance::class), 403, 'User is not authorized to perform this action.');

        $validated = $request->validate([
            'guidance' => ['required', 'array'],
            // No site means every site, which needs every site to be editable.
            'guidance.*.siteId' => [
                'present',
                Rule::requiredIf(! AiGuidance::canEditGuidanceForAllSites()),
                'nullable',
                'integer',
                'distinct',
                Rule::in(Sites::getEditableSiteIds()->all()),
            ],
            'guidance.*.body' => ['present', 'nullable', 'string', 'max:65535'],
        ], [
            'guidance.*.siteId.required' => t('You need permission to edit every site to edit the guidance for all sites.'),
        ]);

        foreach ($validated['guidance'] as $scope) {
            $guidance->save(
                siteId: isset($scope['siteId']) ? (int) $scope['siteId'] : null,
                userId: null,
                body: (string) $scope['body'],
            );
        }

        return $this->asSuccess(t('Guidance saved.'));
    }
}
