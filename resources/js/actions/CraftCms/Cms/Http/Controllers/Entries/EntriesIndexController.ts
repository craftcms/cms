import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/entries'
*/
const EntriesIndexController41ebf3f1ac88880ce793e21c87801e83 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.url(options),
    method: 'get',
})

EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.definition = {
    methods: ["get","head"],
    url: '/admin/entries',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/entries'
*/
EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.url = (options?: RouteQueryOptions) => {
    return EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/entries'
*/
EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/entries'
*/
EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EntriesIndexController41ebf3f1ac88880ce793e21c87801e83.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/content'
*/
const EntriesIndexController45200099be235447e7da5020a01f17b6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EntriesIndexController45200099be235447e7da5020a01f17b6.url(options),
    method: 'get',
})

EntriesIndexController45200099be235447e7da5020a01f17b6.definition = {
    methods: ["get","head"],
    url: '/admin/content',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/content'
*/
EntriesIndexController45200099be235447e7da5020a01f17b6.url = (options?: RouteQueryOptions) => {
    return EntriesIndexController45200099be235447e7da5020a01f17b6.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/content'
*/
EntriesIndexController45200099be235447e7da5020a01f17b6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EntriesIndexController45200099be235447e7da5020a01f17b6.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/EntriesIndexController.php:16
* @route '/admin/content'
*/
EntriesIndexController45200099be235447e7da5020a01f17b6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EntriesIndexController45200099be235447e7da5020a01f17b6.url(options),
    method: 'head',
})

const EntriesIndexController = {
    '/admin/entries': EntriesIndexController41ebf3f1ac88880ce793e21c87801e83,
    '/admin/content': EntriesIndexController45200099be235447e7da5020a01f17b6,
}

export default EntriesIndexController