import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:144
* @route '/admin/actions/sites/save-site'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/save-site',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:144
* @route '/admin/actions/sites/save-site'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:144
* @route '/admin/actions/sites/save-site'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:167
* @route '/admin/actions/sites/reorder-sites'
*/
export const reorder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

reorder.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/reorder-sites',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:167
* @route '/admin/actions/sites/reorder-sites'
*/
reorder.url = (options?: RouteQueryOptions) => {
    return reorder.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::reorder
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:167
* @route '/admin/actions/sites/reorder-sites'
*/
reorder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:180
* @route '/admin/actions/sites/delete-site'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/sites/delete-site',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:180
* @route '/admin/actions/sites/delete-site'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:180
* @route '/admin/actions/sites/delete-site'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:39
* @route '/admin/settings/sites'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sites',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:39
* @route '/admin/settings/sites'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:39
* @route '/admin/settings/sites'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:39
* @route '/admin/settings/sites'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:75
* @route '/admin/settings/sites/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sites/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:75
* @route '/admin/settings/sites/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:75
* @route '/admin/settings/sites/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:75
* @route '/admin/settings/sites/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:113
* @route '/admin/settings/sites/{site}'
*/
export const edit = (args: { site: string | number | { id: string | number } } | [site: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sites/{site}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:113
* @route '/admin/settings/sites/{site}'
*/
edit.url = (args: { site: string | number | { id: string | number } } | [site: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { site: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { site: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            site: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        site: typeof args.site === 'object'
        ? args.site.id
        : args.site,
    }

    return edit.definition.url
            .replace('{site}', parsedArgs.site.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:113
* @route '/admin/settings/sites/{site}'
*/
edit.get = (args: { site: string | number | { id: string | number } } | [site: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SitesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SitesController.php:113
* @route '/admin/settings/sites/{site}'
*/
edit.head = (args: { site: string | number | { id: string | number } } | [site: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

const SitesController = { store, reorder, destroy, index, create, edit }

export default SitesController