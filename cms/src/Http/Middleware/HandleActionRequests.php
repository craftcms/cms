<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\FactoryMuffin\Exceptions\MethodNotFoundException;

class HandleActionRequests
{
    public function __construct(private Application $app)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->has('action')) {
            return $next($request);
        }

        $action = $request->get('action');
        $uri = "actions/{$action}";

        if(str_starts_with($request->path(), config('craft.general.cpTrigger', 'admin'))) {
            $uri = config('craft.general.cpTrigger', 'admin') . '/' . $uri;
        }

        try {
            /** @var \Illuminate\Routing\Route $route */
            $internal = Request::create(
                uri: $uri,
                method: $request->method(),
                parameters: $request->except('action'),
                cookies: $request->cookies->all(),
                files: $request->allFiles(),
                server: $request->server->all(),
            );

            try {
                $response = $this->app->handle($internal);

                if (is_null($response)) {
                    // @todo: Null handling?
                    return $response;
                }

                return $response;
            } catch (ValidationException $e) {
                return redirect()->back()->withErrors($e->errors());
            }
        } catch (MethodNotFoundException) {
        }

        return $next($request);
    }
}
