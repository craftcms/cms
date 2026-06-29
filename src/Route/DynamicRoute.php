<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\pageTemplate;

class DynamicRoute
{
    public function __construct(
        public string $route,
        public array $params = [],
    ) {}

    public function handle(Request $request): Response
    {
        $variables = Arr::pull($this->params, 'variables', []);

        if (in_array($this->route, [
            Cms::config()->actionTrigger.'/templates/render',
            Cms::config()->cpTrigger.'/'.Cms::config()->actionTrigger.'/templates/render',
            'templates/render',
        ])) {
            return response($this->renderTemplate($request, $variables));
        }

        return app()->make(Kernel::class)->handle($request->duplicateWithUri(
            newUri: ActionRoute::uriForSegments(explode('/', trim($this->route, '/')), $request->isCpRequest()),
            query: $variables,
        ));
    }

    private function renderTemplate(Request $request, array $variables = []): string
    {
        $template = Arr::pull($this->params, 'template');
        $publicOnly = Arr::pull($this->params, 'publicOnly', true);

        foreach (TemplateMode::get()->defaultTemplateExtensions() as $extension) {
            $template = Str::beforeLast($template, ".$extension");
        }

        abort_if(Cms::config()->headlessMode && $request->isSiteRequest(), 404);

        $resolvedTemplate = app(TemplateResolver::class)->resolve($template, publicOnly: $publicOnly);

        if ($resolvedTemplate === false) {
            if (app()->hasDebugModeEnabled()) {
                throw new TemplateLoaderException($template, "Template {$template} not found.");
            }

            abort(404);
        }

        if (Str::endsWith($resolvedTemplate, '.blade.php')) {
            return view()->make($template, $variables)->render();
        }

        return pageTemplate($template, $variables);
    }
}
