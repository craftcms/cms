import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/actions/updater'
*/
const index70bc2e414111e49b4ac2eed0e0823e1b = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index70bc2e414111e49b4ac2eed0e0823e1b.url(options),
    method: 'post',
})

index70bc2e414111e49b4ac2eed0e0823e1b.definition = {
    methods: ["post"],
    url: '/admin/actions/updater',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/actions/updater'
*/
index70bc2e414111e49b4ac2eed0e0823e1b.url = (options?: RouteQueryOptions) => {
    return index70bc2e414111e49b4ac2eed0e0823e1b.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/actions/updater'
*/
index70bc2e414111e49b4ac2eed0e0823e1b.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index70bc2e414111e49b4ac2eed0e0823e1b.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/updates'
*/
const indexb4c6c70eb6f8399f423f2a97172d8c4f = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: indexb4c6c70eb6f8399f423f2a97172d8c4f.url(options),
    method: 'post',
})

indexb4c6c70eb6f8399f423f2a97172d8c4f.definition = {
    methods: ["post"],
    url: '/admin/updates',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/updates'
*/
indexb4c6c70eb6f8399f423f2a97172d8c4f.url = (options?: RouteQueryOptions) => {
    return indexb4c6c70eb6f8399f423f2a97172d8c4f.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:75
* @route '/admin/updates'
*/
indexb4c6c70eb6f8399f423f2a97172d8c4f.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: indexb4c6c70eb6f8399f423f2a97172d8c4f.url(options),
    method: 'post',
})

export const index = {
    '/admin/actions/updater': index70bc2e414111e49b4ac2eed0e0823e1b,
    '/admin/updates': indexb4c6c70eb6f8399f423f2a97172d8c4f,
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::forceUpdate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:56
* @route '/admin/actions/updater/force-update'
*/
export const forceUpdate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forceUpdate.url(options),
    method: 'post',
})

forceUpdate.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/force-update',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::forceUpdate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:56
* @route '/admin/actions/updater/force-update'
*/
forceUpdate.url = (options?: RouteQueryOptions) => {
    return forceUpdate.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::forceUpdate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:56
* @route '/admin/actions/updater/force-update'
*/
forceUpdate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forceUpdate.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::backup
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:61
* @route '/admin/actions/updater/backup'
*/
export const backup = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: backup.url(options),
    method: 'post',
})

backup.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/backup',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::backup
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:61
* @route '/admin/actions/updater/backup'
*/
backup.url = (options?: RouteQueryOptions) => {
    return backup.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::backup
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:61
* @route '/admin/actions/updater/backup'
*/
backup.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: backup.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::serverCheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:111
* @route '/admin/actions/updater/server-check'
*/
export const serverCheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: serverCheck.url(options),
    method: 'post',
})

serverCheck.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/server-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::serverCheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:111
* @route '/admin/actions/updater/server-check'
*/
serverCheck.url = (options?: RouteQueryOptions) => {
    return serverCheck.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::serverCheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:111
* @route '/admin/actions/updater/server-check'
*/
serverCheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: serverCheck.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::revert
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:90
* @route '/admin/actions/updater/revert'
*/
export const revert = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: revert.url(options),
    method: 'post',
})

revert.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/revert',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::revert
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:90
* @route '/admin/actions/updater/revert'
*/
revert.url = (options?: RouteQueryOptions) => {
    return revert.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::revert
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:90
* @route '/admin/actions/updater/revert'
*/
revert.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: revert.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:152
* @route '/admin/actions/updater/migrate'
*/
export const migrate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: migrate.url(options),
    method: 'post',
})

migrate.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/migrate',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:152
* @route '/admin/actions/updater/migrate'
*/
migrate.url = (options?: RouteQueryOptions) => {
    return migrate.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::migrate
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:152
* @route '/admin/actions/updater/migrate'
*/
migrate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: migrate.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:95
* @route '/admin/actions/updater/precheck'
*/
export const precheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

precheck.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/precheck',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:95
* @route '/admin/actions/updater/precheck'
*/
precheck.url = (options?: RouteQueryOptions) => {
    return precheck.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::precheck
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:95
* @route '/admin/actions/updater/precheck'
*/
precheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: precheck.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:139
* @route '/admin/actions/updater/recheck-composer'
*/
export const recheckComposer = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

recheckComposer.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/recheck-composer',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:139
* @route '/admin/actions/updater/recheck-composer'
*/
recheckComposer.url = (options?: RouteQueryOptions) => {
    return recheckComposer.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::recheckComposer
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:139
* @route '/admin/actions/updater/recheck-composer'
*/
recheckComposer.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recheckComposer.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:144
* @route '/admin/actions/updater/composer-install'
*/
export const composerInstall = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

composerInstall.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/composer-install',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:144
* @route '/admin/actions/updater/composer-install'
*/
composerInstall.url = (options?: RouteQueryOptions) => {
    return composerInstall.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerInstall
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:144
* @route '/admin/actions/updater/composer-install'
*/
composerInstall.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerInstall.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:172
* @route '/admin/actions/updater/composer-remove'
*/
export const composerRemove = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

composerRemove.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/composer-remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:172
* @route '/admin/actions/updater/composer-remove'
*/
composerRemove.url = (options?: RouteQueryOptions) => {
    return composerRemove.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::composerRemove
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:172
* @route '/admin/actions/updater/composer-remove'
*/
composerRemove.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: composerRemove.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:195
* @route '/admin/actions/updater/finish'
*/
export const finish = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

finish.definition = {
    methods: ["post"],
    url: '/admin/actions/updater/finish',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:195
* @route '/admin/actions/updater/finish'
*/
finish.url = (options?: RouteQueryOptions) => {
    return finish.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Updates\UpdaterController::finish
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Updates/UpdaterController.php:195
* @route '/admin/actions/updater/finish'
*/
finish.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finish.url(options),
    method: 'post',
})

const UpdaterController = { index, forceUpdate, backup, serverCheck, revert, migrate, precheck, recheckComposer, composerInstall, composerRemove, finish }

export default UpdaterController