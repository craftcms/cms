import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateDb
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:96
* @route '/admin/actions/install/validate-db'
*/
export const validateDb = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateDb.url(options),
    method: 'post',
})

validateDb.definition = {
    methods: ["post"],
    url: '/admin/actions/install/validate-db',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateDb
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:96
* @route '/admin/actions/install/validate-db'
*/
validateDb.url = (options?: RouteQueryOptions) => {
    return validateDb.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateDb
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:96
* @route '/admin/actions/install/validate-db'
*/
validateDb.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateDb.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateAccount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:131
* @route '/admin/actions/install/validate-account'
*/
export const validateAccount = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateAccount.url(options),
    method: 'post',
})

validateAccount.definition = {
    methods: ["post"],
    url: '/admin/actions/install/validate-account',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateAccount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:131
* @route '/admin/actions/install/validate-account'
*/
validateAccount.url = (options?: RouteQueryOptions) => {
    return validateAccount.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateAccount
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:131
* @route '/admin/actions/install/validate-account'
*/
validateAccount.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateAccount.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateSite
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:142
* @route '/admin/actions/install/validate-site'
*/
export const validateSite = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateSite.url(options),
    method: 'post',
})

validateSite.definition = {
    methods: ["post"],
    url: '/admin/actions/install/validate-site',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateSite
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:142
* @route '/admin/actions/install/validate-site'
*/
validateSite.url = (options?: RouteQueryOptions) => {
    return validateSite.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::validateSite
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:142
* @route '/admin/actions/install/validate-site'
*/
validateSite.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateSite.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:157
* @route '/admin/actions/install/install'
*/
export const install = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: install.url(options),
    method: 'post',
})

install.definition = {
    methods: ["post"],
    url: '/admin/actions/install/install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:157
* @route '/admin/actions/install/install'
*/
install.url = (options?: RouteQueryOptions) => {
    return install.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::install
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:157
* @route '/admin/actions/install/install'
*/
install.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: install.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:51
* @route '/admin/install'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/install',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:51
* @route '/admin/install'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:51
* @route '/admin/install'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\InstallController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/InstallController.php:51
* @route '/admin/install'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const InstallController = { validateDb, validateAccount, validateSite, install, index }

export default InstallController