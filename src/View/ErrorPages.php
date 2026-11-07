<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Route\Events\Redirecting;
use CraftCms\Cms\Route\RedirectRule;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Support\Url;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Responds to exceptions on Craft routes: applies `config/redirects.php` rules to 404s, and renders
 * error templates. Routes outside Craft keep Laravel’s error handling.
 *
 * Site requests look for `errors/<code>`, then `errors/offline` for 503s, then `errors/error`, each
 * in the current site’s template folder first and as a public or private template. When there’s no
 * site template, or on control panel requests, the control panel’s own error templates are used.
 *
 * @since 6.0.0
 */
readonly class ErrorPages
{
    public function __construct(
        private TemplateResolver $templateResolver,
        private Sites $sites,
    ) {}

    public function render(Throwable $e, Request $request, Handler $handler): ?Response
    {
        if (! in_array('craft', $request->route()?->middleware() ?? [], true)) {
            return null;
        }

        // Laravel renders these itself after the renderable callbacks.
        if ($e instanceof HttpResponseException || $e instanceof AuthenticationException || $e instanceof ValidationException) {
            return null;
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 404) {
            if ($redirect = $this->redirect($request)) {
                return $redirect;
            }

            if (! $request->isCpRequest() && $this->sites->getRequestPath($request) === 'wp-admin') {
                return $handler->render($request, new HttpException(418, $e->getMessage(), $e, $e->getHeaders()));
            }
        }

        if (! $e instanceof HttpExceptionInterface) {
            if (app()->hasDebugModeEnabled()) {
                return null;
            }

            if ($this->showsExceptionDetails($request)) {
                return $this->withDebugMode(fn () => $handler->render($request, $e));
            }
        }

        if ((fn (): bool => $this->shouldReturnJson($request, $e))->call($handler)) {
            return null;
        }

        $httpException = $e instanceof HttpExceptionInterface ? $e : new HttpException(500, t('Server Error'), $e);
        $statusCode = $httpException->getStatusCode();

        try {
            [$template, $templateMode] = $this->findTemplate($request, $statusCode);

            return response(Template::renderPageTemplate($template, [
                'message' => $httpException->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'statusCode' => $statusCode,
                'exception' => $httpException,
                'errors' => new ViewErrorBag,
                ...get_object_vars($e),
            ], $templateMode), $statusCode, $httpException->getHeaders());
        } catch (Throwable $renderException) {
            if (app()->hasDebugModeEnabled()) {
                throw $renderException;
            }

            report($renderException);

            return null;
        }
    }

    private function redirect(Request $request): ?Response
    {
        foreach (config('craft.redirects', []) as $from => $rule) {
            $rule = RedirectRule::fromConfig($from, $rule);
            $url = $rule->getMatch($request);

            if ($url === null) {
                continue;
            }

            event(new Redirecting($rule));

            return redirect(Url::encodeUrl(Url::url($url)), $rule->statusCode);
        }

        return null;
    }

    /**
     * @return array{string, TemplateMode}
     */
    private function findTemplate(Request $request, int $statusCode): array
    {
        if (! $request->isCpRequest()) {
            $names = [(string) $statusCode, ...($statusCode === 503 ? ['offline'] : []), 'error'];
            $folders = [...(Cms::isInstalled() ? [$this->sites->getCurrentSite()->handle.'/errors'] : []), 'errors'];
            $trigger = TemplateMode::Site->privateTemplateTrigger();

            foreach ($names as $name) {
                foreach ($folders as $folder) {
                    foreach (array_unique([$name, $trigger.$name]) as $filename) {
                        if ($this->templateResolver->exists("$folder/$filename", TemplateMode::Site)) {
                            return ["$folder/$filename", TemplateMode::Site];
                        }
                    }
                }
            }
        }

        $template = $this->templateResolver->exists("errors/$statusCode", TemplateMode::Cp) ? "errors/$statusCode" : 'error';

        return [$template, TemplateMode::Cp];
    }

    /**
     * Whether the user is an admin who has chosen to see exception details when debug mode is off.
     */
    private function showsExceptionDetails(Request $request): bool
    {
        try {
            $user = $request->craftUser();
        } catch (Throwable) {
            // The exception may have come from the database or session the user is loaded from.
            return false;
        }

        return $user?->isAdmin() && $user->getPreference('showExceptionView');
    }

    private function withDebugMode(Closure $callback): Response
    {
        $debug = config('app.debug');
        config(['app.debug' => true]);
        $this->registerExceptionRenderer();

        try {
            return $callback();
        } finally {
            config(['app.debug' => $debug]);
        }
    }

    /**
     * Registers Laravel’s exception page, which Laravel only registers when debug mode is on at boot.
     *
     * @see FoundationServiceProvider::registerExceptionRenderer()
     */
    private function registerExceptionRenderer(): void
    {
        if (app()->bound(Renderer::class)) {
            return;
        }

        view()->addNamespace(
            'laravel-exceptions-renderer',
            dirname((string) new ReflectionClass(Renderer::class)->getFileName(), 3).'/resources/exceptions/renderer',
        );

        app()->singleton(Renderer::class, fn (Application $app) => new Renderer(
            $app->make(Factory::class),
            $app->make(Listener::class),
            new HtmlErrorRenderer(true),
            $app->make(BladeMapper::class),
            $app->basePath(),
        ));
    }
}
