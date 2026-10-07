<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Route\TemplateRoute;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
readonly class AuthenticationViews
{
    public function __construct(
        private TemplateResolver $templates,
        private HandleInertiaRequests $inertia,
    ) {}

    /**
     * @param  array<string, mixed>|null  $inertiaProps
     * @param  array<string, mixed>  $templateData
     */
    public function render(
        Request $request,
        string $inertiaComponent,
        ?array $inertiaProps = null,
        array $templateData = [],
    ): View|InertiaResponse|Response {
        if (! $request->isCpRequest() && $this->templates->exists($request->craftPath(), TemplateMode::Site)) {
            return new TemplateRoute($request->craftPath(), $templateData, publicOnly: false)->handle($request);
        }

        TemplateMode::set(TemplateMode::Cp);

        if ($request->isCpRequest()) {
            return Inertia::render($inertiaComponent, $inertiaProps ?? $templateData);
        }

        return $this->inertia->handle(
            $request,
            fn (Request $request): Response => Inertia::render($inertiaComponent, $inertiaProps ?? $templateData)->toResponse($request),
        );
    }
}
