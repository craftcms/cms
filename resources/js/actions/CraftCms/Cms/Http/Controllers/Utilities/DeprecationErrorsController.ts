import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::getDeprecationErrorTracesModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:29
* @route '/admin/actions/utilities/get-deprecation-error-traces-modal'
*/
export const getDeprecationErrorTracesModal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getDeprecationErrorTracesModal.url(options),
    method: 'post',
})

getDeprecationErrorTracesModal.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/get-deprecation-error-traces-modal',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::getDeprecationErrorTracesModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:29
* @route '/admin/actions/utilities/get-deprecation-error-traces-modal'
*/
getDeprecationErrorTracesModal.url = (options?: RouteQueryOptions) => {
    return getDeprecationErrorTracesModal.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::getDeprecationErrorTracesModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:29
* @route '/admin/actions/utilities/get-deprecation-error-traces-modal'
*/
getDeprecationErrorTracesModal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getDeprecationErrorTracesModal.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteDeprecationError
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:44
* @route '/admin/actions/utilities/delete-deprecation-error'
*/
export const deleteDeprecationError = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteDeprecationError.url(options),
    method: 'post',
})

deleteDeprecationError.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/delete-deprecation-error',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteDeprecationError
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:44
* @route '/admin/actions/utilities/delete-deprecation-error'
*/
deleteDeprecationError.url = (options?: RouteQueryOptions) => {
    return deleteDeprecationError.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteDeprecationError
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:44
* @route '/admin/actions/utilities/delete-deprecation-error'
*/
deleteDeprecationError.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteDeprecationError.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteAllDeprecationErrors
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:55
* @route '/admin/actions/utilities/delete-all-deprecation-errors'
*/
export const deleteAllDeprecationErrors = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteAllDeprecationErrors.url(options),
    method: 'post',
})

deleteAllDeprecationErrors.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/delete-all-deprecation-errors',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteAllDeprecationErrors
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:55
* @route '/admin/actions/utilities/delete-all-deprecation-errors'
*/
deleteAllDeprecationErrors.url = (options?: RouteQueryOptions) => {
    return deleteAllDeprecationErrors.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController::deleteAllDeprecationErrors
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/DeprecationErrorsController.php:55
* @route '/admin/actions/utilities/delete-all-deprecation-errors'
*/
deleteAllDeprecationErrors.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteAllDeprecationErrors.url(options),
    method: 'post',
})

const DeprecationErrorsController = { getDeprecationErrorTracesModal, deleteDeprecationError, deleteAllDeprecationErrors }

export default DeprecationErrorsController