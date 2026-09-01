<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateRoute
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public string $template,
        public array $variables = [],
        public bool $publicOnly = true,
    ) {}

    public function handle(Request $request): Response
    {
        $template = $this->template;

        foreach (TemplateMode::get()->defaultTemplateExtensions() as $extension) {
            $template = Str::beforeLast($template, ".$extension");
        }

        abort_if(Cms::config()->headlessMode && $request->isSiteRequest(), 404);

        return response(Template::renderPageTemplate(
            $template,
            $this->variables,
            publicOnly: $this->publicOnly,
        ));
    }
}
