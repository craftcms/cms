import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::rebuild
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:37
* @route '/admin/actions/project-config/rebuild'
*/
export const rebuild = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rebuild.url(options),
    method: 'post',
})

rebuild.definition = {
    methods: ["post"],
    url: '/admin/actions/project-config/rebuild',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::rebuild
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:37
* @route '/admin/actions/project-config/rebuild'
*/
rebuild.url = (options?: RouteQueryOptions) => {
    return rebuild.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::rebuild
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:37
* @route '/admin/actions/project-config/rebuild'
*/
rebuild.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rebuild.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::diff
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:32
* @route '/admin/actions/project-config/diff'
*/
export const diff = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: diff.url(options),
    method: 'get',
})

diff.definition = {
    methods: ["get","head"],
    url: '/admin/actions/project-config/diff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::diff
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:32
* @route '/admin/actions/project-config/diff'
*/
diff.url = (options?: RouteQueryOptions) => {
    return diff.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::diff
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:32
* @route '/admin/actions/project-config/diff'
*/
diff.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: diff.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::diff
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:32
* @route '/admin/actions/project-config/diff'
*/
diff.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: diff.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::discard
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:46
* @route '/admin/actions/project-config/discard'
*/
export const discard = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: discard.url(options),
    method: 'post',
})

discard.definition = {
    methods: ["post"],
    url: '/admin/actions/project-config/discard',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::discard
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:46
* @route '/admin/actions/project-config/discard'
*/
discard.url = (options?: RouteQueryOptions) => {
    return discard.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::discard
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:46
* @route '/admin/actions/project-config/discard'
*/
discard.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: discard.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::download
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:55
* @route '/admin/actions/project-config/download'
*/
export const download = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/admin/actions/project-config/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::download
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:55
* @route '/admin/actions/project-config/download'
*/
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::download
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:55
* @route '/admin/actions/project-config/download'
*/
download.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController::download
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/ProjectConfigController.php:55
* @route '/admin/actions/project-config/download'
*/
download.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(options),
    method: 'head',
})

const ProjectConfigController = { rebuild, diff, discard, download }

export default ProjectConfigController