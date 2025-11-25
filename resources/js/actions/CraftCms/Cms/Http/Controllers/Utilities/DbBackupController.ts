import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DbBackupController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DbBackupController.php:26
* @route '/admin/actions/utilities/db-backup-perform-action'
*/
const DbBackupController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: DbBackupController.url(options),
    method: 'post',
})

DbBackupController.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/db-backup-perform-action',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DbBackupController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DbBackupController.php:26
* @route '/admin/actions/utilities/db-backup-perform-action'
*/
DbBackupController.url = (options?: RouteQueryOptions) => {
    return DbBackupController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DbBackupController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DbBackupController.php:26
* @route '/admin/actions/utilities/db-backup-perform-action'
*/
DbBackupController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: DbBackupController.url(options),
    method: 'post',
})

export default DbBackupController