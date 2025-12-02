import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:119
* @route '/admin/actions/user-settings/save-group'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/user-settings/save-group',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:119
* @route '/admin/actions/user-settings/save-group'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:119
* @route '/admin/actions/user-settings/save-group'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:175
* @route '/admin/actions/user-settings/delete-group'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/user-settings/delete-group',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:175
* @route '/admin/actions/user-settings/delete-group'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:175
* @route '/admin/actions/user-settings/delete-group'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:37
* @route '/admin/settings/users'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/users',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:37
* @route '/admin/settings/users'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:37
* @route '/admin/settings/users'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:37
* @route '/admin/settings/users'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:46
* @route '/admin/settings/users/groups/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/settings/users/groups/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:46
* @route '/admin/settings/users/groups/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:46
* @route '/admin/settings/users/groups/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:46
* @route '/admin/settings/users/groups/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:70
* @route '/admin/settings/users/groups/{userGroup}'
*/
export const edit = (args: { userGroup: string | number | { id: string | number } } | [userGroup: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/settings/users/groups/{userGroup}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:70
* @route '/admin/settings/users/groups/{userGroup}'
*/
edit.url = (args: { userGroup: string | number | { id: string | number } } | [userGroup: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { userGroup: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { userGroup: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            userGroup: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        userGroup: typeof args.userGroup === 'object'
        ? args.userGroup.id
        : args.userGroup,
    }

    return edit.definition.url
            .replace('{userGroup}', parsedArgs.userGroup.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:70
* @route '/admin/settings/users/groups/{userGroup}'
*/
edit.get = (args: { userGroup: string | number | { id: string | number } } | [userGroup: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\UserGroupsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/UserGroupsController.php:70
* @route '/admin/settings/users/groups/{userGroup}'
*/
edit.head = (args: { userGroup: string | number | { id: string | number } } | [userGroup: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

const UserGroupsController = { store, destroy, index, create, edit }

export default UserGroupsController