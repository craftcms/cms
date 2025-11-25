import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\MigrateController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/MigrateController.php:30
* @route '/actions/migrate'
*/
const MigrateController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: MigrateController.url(options),
    method: 'post',
})

MigrateController.definition = {
    methods: ["post"],
    url: '/actions/migrate',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\MigrateController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/MigrateController.php:30
* @route '/actions/migrate'
*/
MigrateController.url = (options?: RouteQueryOptions) => {
    return MigrateController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\MigrateController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/MigrateController.php:30
* @route '/actions/migrate'
*/
MigrateController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: MigrateController.url(options),
    method: 'post',
})

export default MigrateController