import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::showModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:37
* @route '/admin/actions/entries/move-to-section-modal-data'
*/
export const showModal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: showModal.url(options),
    method: 'post',
})

showModal.definition = {
    methods: ["post"],
    url: '/admin/actions/entries/move-to-section-modal-data',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::showModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:37
* @route '/admin/actions/entries/move-to-section-modal-data'
*/
showModal.url = (options?: RouteQueryOptions) => {
    return showModal.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::showModal
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:37
* @route '/admin/actions/entries/move-to-section-modal-data'
*/
showModal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: showModal.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::move
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:109
* @route '/admin/actions/entries/move-to-section'
*/
export const move = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(options),
    method: 'post',
})

move.definition = {
    methods: ["post"],
    url: '/admin/actions/entries/move-to-section',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::move
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:109
* @route '/admin/actions/entries/move-to-section'
*/
move.url = (options?: RouteQueryOptions) => {
    return move.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController::move
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/MoveEntryToSectionController.php:109
* @route '/admin/actions/entries/move-to-section'
*/
move.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(options),
    method: 'post',
})

const MoveEntryToSectionController = { showModal, move }

export default MoveEntryToSectionController