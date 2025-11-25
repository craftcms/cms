import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/actions/entries/save-entry'
*/
const StoreEntryControllere36862da41a30b046a77329a6c6e57d5 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StoreEntryControllere36862da41a30b046a77329a6c6e57d5.url(options),
    method: 'post',
})

StoreEntryControllere36862da41a30b046a77329a6c6e57d5.definition = {
    methods: ["post"],
    url: '/actions/entries/save-entry',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/actions/entries/save-entry'
*/
StoreEntryControllere36862da41a30b046a77329a6c6e57d5.url = (options?: RouteQueryOptions) => {
    return StoreEntryControllere36862da41a30b046a77329a6c6e57d5.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/actions/entries/save-entry'
*/
StoreEntryControllere36862da41a30b046a77329a6c6e57d5.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StoreEntryControllere36862da41a30b046a77329a6c6e57d5.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/admin/actions/entries/save-entry'
*/
const StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.url(options),
    method: 'post',
})

StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.definition = {
    methods: ["post"],
    url: '/admin/actions/entries/save-entry',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/admin/actions/entries/save-entry'
*/
StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.url = (options?: RouteQueryOptions) => {
    return StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\StoreEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/StoreEntryController.php:38
* @route '/admin/actions/entries/save-entry'
*/
StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee.url(options),
    method: 'post',
})

const StoreEntryController = {
    '/actions/entries/save-entry': StoreEntryControllere36862da41a30b046a77329a6c6e57d5,
    '/admin/actions/entries/save-entry': StoreEntryController35f3b74f8aa5fbbc0d056c6eba3bb7ee,
}

export default StoreEntryController