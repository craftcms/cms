import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})

preview.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/actions/preview/preview',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: preview.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: preview.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: preview.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: preview.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::preview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:53
* @route '/actions/preview/preview'
*/
preview.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: preview.url(options),
    method: 'options',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
export const createToken = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createToken.url(options),
    method: 'get',
})

createToken.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/admin/actions/preview/create-token',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.url = (options?: RouteQueryOptions) => {
    return createToken.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createToken.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createToken.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createToken.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: createToken.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: createToken.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: createToken.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\PreviewController::createToken
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/PreviewController.php:25
* @route '/admin/actions/preview/create-token'
*/
createToken.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: createToken.url(options),
    method: 'options',
})

const PreviewController = { preview, createToken }

export default PreviewController