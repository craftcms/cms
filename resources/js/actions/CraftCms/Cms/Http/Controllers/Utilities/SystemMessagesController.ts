import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:36
* @route '/admin/actions/system-messages/get-message-modal'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: show.url(options),
    method: 'post',
})

show.definition = {
    methods: ["post"],
    url: '/admin/actions/system-messages/get-message-modal',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:36
* @route '/admin/actions/system-messages/get-message-modal'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::show
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:36
* @route '/admin/actions/system-messages/get-message-modal'
*/
show.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: show.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:53
* @route '/admin/actions/system-messages/save-message'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/system-messages/save-message',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:53
* @route '/admin/actions/system-messages/save-message'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Utilities/SystemMessagesController.php:53
* @route '/admin/actions/system-messages/save-message'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const SystemMessagesController = { show, store }

export default SystemMessagesController