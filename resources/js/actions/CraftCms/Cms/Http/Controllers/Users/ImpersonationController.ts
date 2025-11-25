import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
export const withToken = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: withToken.url(options),
    method: 'get',
})

withToken.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/actions/users/impersonate-with-token',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.url = (options?: RouteQueryOptions) => {
    return withToken.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: withToken.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: withToken.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withToken.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: withToken.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: withToken.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: withToken.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::withToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:86
* @route '/actions/users/impersonate-with-token'
*/
withToken.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: withToken.url(options),
    method: 'options',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::impersonate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:32
* @route '/admin/actions/users/impersonate'
*/
export const impersonate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: impersonate.url(options),
    method: 'post',
})

impersonate.definition = {
    methods: ["post"],
    url: '/admin/actions/users/impersonate',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::impersonate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:32
* @route '/admin/actions/users/impersonate'
*/
impersonate.url = (options?: RouteQueryOptions) => {
    return impersonate.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::impersonate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:32
* @route '/admin/actions/users/impersonate'
*/
impersonate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: impersonate.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::getUrl
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:63
* @route '/admin/actions/users/get-impersonation-url'
*/
export const getUrl = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getUrl.url(options),
    method: 'post',
})

getUrl.definition = {
    methods: ["post"],
    url: '/admin/actions/users/get-impersonation-url',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::getUrl
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:63
* @route '/admin/actions/users/get-impersonation-url'
*/
getUrl.url = (options?: RouteQueryOptions) => {
    return getUrl.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Users\ImpersonationController::getUrl
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Users/ImpersonationController.php:63
* @route '/admin/actions/users/get-impersonation-url'
*/
getUrl.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getUrl.url(options),
    method: 'post',
})

const ImpersonationController = { withToken, impersonate, getUrl }

export default ImpersonationController