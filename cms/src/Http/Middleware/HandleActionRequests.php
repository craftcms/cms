<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\FactoryMuffin\Exceptions\MethodNotFoundException;

class HandleActionRequests
{
    public function __construct(private Application $app) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->has('action')) {
            return $next($request);
        }

        $action = $request->get('action');
        $uri = "actions/{$action}";

        if (str_starts_with($request->path(), config('craft.general.cpTrigger', 'admin'))) {
            $uri = config('craft.general.cpTrigger', 'admin').'/'.$uri;
        }

        try {
            $internal = Request::create(
                uri: $uri,
                method: $request->method(),
                parameters: $request->except('action'),
                cookies: $request->cookies->all(),
                files: $request->allFiles(),
                server: $request->server->all(),
            );

            try {
                // @todo null handling?
                return $this->app->handle($internal);
            } catch (ValidationException $e) {
                return redirect()->back()->withErrors($e->errors());
            }
        } catch (MethodNotFoundException) {
        }

        return $next($request);
    }
}
