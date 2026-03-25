<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
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
        $variables = Arr::pull($this->params, 'variables', []) + $request->query->all();

        if (in_array($this->route, [
            Cms::config()->actionTrigger.'/templates/render',
            Cms::config()->cpTrigger.'/'.Cms::config()->actionTrigger.'/templates/render',
            'templates/render',
        ])) {
            return response($this->renderTemplate($request, $variables));
        }

        return app()->make(Kernel::class)->handle($request->duplicateWithUri(
            newUri: $request->actionSegmentsToRoute(explode('/', trim($this->route, '/'))),
            query: $variables,
        ));
    }

    private function renderTemplate(Request $request, array $variables = []): string
    {
        $template = Arr::pull($this->params, 'template');

        foreach (TemplateMode::get()->defaultTemplateExtensions() as $extension) {
            $template = Str::beforeLast($template, ".$extension");
        }

        abort_if(Cms::config()->headlessMode && $request->isSiteRequest(), 404);

        if (view()->exists($template)) {
            return view($template, $variables)->render();
        }

        abort_if(app(TemplateResolver::class)->resolve($template, publicOnly: true) === false, 404);

        return pageTemplate($template, $variables);
    }
}
