import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::getElementLevelDelta
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:67
* @route '/admin/actions/structures/get-element-level-delta'
*/
export const getElementLevelDelta = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getElementLevelDelta.url(options),
    method: 'post',
})

getElementLevelDelta.definition = {
    methods: ["post"],
    url: '/admin/actions/structures/get-element-level-delta',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::getElementLevelDelta
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:67
* @route '/admin/actions/structures/get-element-level-delta'
*/
getElementLevelDelta.url = (options?: RouteQueryOptions) => {
    return getElementLevelDelta.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::getElementLevelDelta
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:67
* @route '/admin/actions/structures/get-element-level-delta'
*/
getElementLevelDelta.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getElementLevelDelta.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::moveElement
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:74
* @route '/admin/actions/structures/move-element'
*/
export const moveElement = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: moveElement.url(options),
    method: 'post',
})

moveElement.definition = {
    methods: ["post"],
    url: '/admin/actions/structures/move-element',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::moveElement
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:74
* @route '/admin/actions/structures/move-element'
*/
moveElement.url = (options?: RouteQueryOptions) => {
    return moveElement.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\StructuresController::moveElement
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/StructuresController.php:74
* @route '/admin/actions/structures/move-element'
*/
moveElement.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: moveElement.url(options),
    method: 'post',
})

const StructuresController = { getElementLevelDelta, moveElement }

export default StructuresController