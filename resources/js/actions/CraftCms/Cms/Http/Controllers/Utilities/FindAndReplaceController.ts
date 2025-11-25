import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/FindAndReplaceController.php:22
* @route '/admin/actions/utilities/find-and-replace-perform-action'
*/
const FindAndReplaceController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: FindAndReplaceController.url(options),
    method: 'post',
})

FindAndReplaceController.definition = {
    methods: ["post"],
    url: '/admin/actions/utilities/find-and-replace-perform-action',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/FindAndReplaceController.php:22
* @route '/admin/actions/utilities/find-and-replace-perform-action'
*/
FindAndReplaceController.url = (options?: RouteQueryOptions) => {
    return FindAndReplaceController.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/FindAndReplaceController.php:22
* @route '/admin/actions/utilities/find-and-replace-perform-action'
*/
FindAndReplaceController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: FindAndReplaceController.url(options),
    method: 'post',
})

export default FindAndReplaceController