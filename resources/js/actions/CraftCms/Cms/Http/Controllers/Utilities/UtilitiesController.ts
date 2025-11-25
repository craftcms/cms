import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
export const badgeCount = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: badgeCount.url(options),
    method: 'get',
})

badgeCount.definition = {
    methods: ["get","head","post","put","patch","delete","options"],
    url: '/admin/actions/app/get-utilities-badge-count',
} satisfies RouteDefinition<["get","head","post","put","patch","delete","options"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.url = (options?: RouteQueryOptions) => {
    return badgeCount.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: badgeCount.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: badgeCount.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: badgeCount.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: badgeCount.url(options),
    method: 'put',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: badgeCount.url(options),
    method: 'patch',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: badgeCount.url(options),
    method: 'delete',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::badgeCount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:27
* @route '/admin/actions/app/get-utilities-badge-count'
*/
badgeCount.options = (options?: RouteQueryOptions): RouteDefinition<'options'> => ({
    url: badgeCount.url(options),
    method: 'options',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:34
* @route '/admin/utilities'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/utilities',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:34
* @route '/admin/utilities'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:34
* @route '/admin/utilities'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:34
* @route '/admin/utilities'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:49
* @route '/admin/utilities/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/utilities/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:49
* @route '/admin/utilities/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:49
* @route '/admin/utilities/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/UtilitiesController.php:49
* @route '/admin/utilities/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const UtilitiesController = { badgeCount, index, show }

export default UtilitiesController