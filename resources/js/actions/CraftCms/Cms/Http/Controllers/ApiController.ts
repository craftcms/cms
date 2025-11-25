import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
export const headers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: headers.url(options),
    method: 'get',
})

headers.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/admin/actions/app/api-headers',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.url = (options?: RouteQueryOptions) => {
    return headers.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: headers.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: headers.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: headers.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: headers.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: headers.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: headers.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::headers
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:17
* @route '/admin/actions/app/api-headers'
*/
headers.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: headers.url(options),
    method: 'options',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
export const processResponseHeaders = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: processResponseHeaders.url(options),
    method: 'get',
})

processResponseHeaders.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/admin/actions/app/process-api-response-headers',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.url = (options?: RouteQueryOptions) => {
    return processResponseHeaders.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: processResponseHeaders.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: processResponseHeaders.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processResponseHeaders.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: processResponseHeaders.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: processResponseHeaders.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: processResponseHeaders.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\ApiController::processResponseHeaders
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/ApiController.php:22
* @route '/admin/actions/app/process-api-response-headers'
*/
processResponseHeaders.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: processResponseHeaders.url(options),
    method: 'options',
})

const ApiController = { headers, processResponseHeaders }

export default ApiController