import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\MigrationsController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/MigrationsController.php:26
* @route '/admin/actions/utilities/apply-new-migrations'
*/
const MigrationsController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: MigrationsController.url(options),
    method: 'post',
})

MigrationsController.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/apply-new-migrations',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\MigrationsController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/MigrationsController.php:26
* @route '/admin/actions/utilities/apply-new-migrations'
*/
MigrationsController.url = (options?: RouteQueryOptions) => {
    return MigrationsController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\MigrationsController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/MigrationsController.php:26
* @route '/admin/actions/utilities/apply-new-migrations'
*/
MigrationsController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: MigrationsController.url(options),
    method: 'post',
})

export default MigrationsController