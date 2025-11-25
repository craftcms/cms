import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults, validateParameters } from './../../../../../wayfinder'
/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::start
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:18
* @route '/_workbench'
*/
export const start = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: start.url(options),
    method: 'get',
})

start.definition = {
    methods: ["get","head"],
    url: '/_workbench',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::start
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:18
* @route '/_workbench'
*/
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::start
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:18
* @route '/_workbench'
*/
start.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: start.url(options),
    method: 'get',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::start
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:18
* @route '/_workbench'
*/
start.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: start.url(options),
    method: 'head',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::login
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:60
* @route '/_workbench/login/{userId}/{guard?}'
*/
export const login = (args: { userId: string | number, guard?: string | number } | [userId: string | number, guard: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(args, options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/_workbench/login/{userId}/{guard?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::login
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:60
* @route '/_workbench/login/{userId}/{guard?}'
*/
login.url = (args: { userId: string | number, guard?: string | number } | [userId: string | number, guard: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            userId: args[0],
            guard: args[1],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "guard",
    ])

    const parsedArgs = {
        userId: args.userId,
        guard: args.guard,
    }

    return login.definition.url
            .replace('{userId}', parsedArgs.userId.toString())
            .replace('{guard?}', parsedArgs.guard?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::login
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:60
* @route '/_workbench/login/{userId}/{guard?}'
*/
login.get = (args: { userId: string | number, guard?: string | number } | [userId: string | number, guard: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(args, options),
    method: 'get',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::login
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:60
* @route '/_workbench/login/{userId}/{guard?}'
*/
login.head = (args: { userId: string | number, guard?: string | number } | [userId: string | number, guard: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(args, options),
    method: 'head',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::logout
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:84
* @route '/_workbench/logout/{guard?}'
*/
export const logout = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logout.url(args, options),
    method: 'get',
})

logout.definition = {
    methods: ["get","head"],
    url: '/_workbench/logout/{guard?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::logout
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:84
* @route '/_workbench/logout/{guard?}'
*/
logout.url = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { guard: args }
    }

    if (Array.isArray(args)) {
        args = {
            guard: args[0],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "guard",
    ])

    const parsedArgs = {
        guard: args?.guard,
    }

    return logout.definition.url
            .replace('{guard?}', parsedArgs.guard?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::logout
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:84
* @route '/_workbench/logout/{guard?}'
*/
logout.get = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logout.url(args, options),
    method: 'get',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::logout
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:84
* @route '/_workbench/logout/{guard?}'
*/
logout.head = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logout.url(args, options),
    method: 'head',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::user
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:39
* @route '/_workbench/user/{guard?}'
*/
export const user = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: user.url(args, options),
    method: 'get',
})

user.definition = {
    methods: ["get","head"],
    url: '/_workbench/user/{guard?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::user
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:39
* @route '/_workbench/user/{guard?}'
*/
user.url = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { guard: args }
    }

    if (Array.isArray(args)) {
        args = {
            guard: args[0],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "guard",
    ])

    const parsedArgs = {
        guard: args?.guard,
    }

    return user.definition.url
            .replace('{guard?}', parsedArgs.guard?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::user
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:39
* @route '/_workbench/user/{guard?}'
*/
user.get = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: user.url(args, options),
    method: 'get',
})

/**
* @see \Orchestra\Workbench\Http\Controllers\WorkbenchController::user
* @see Users/brianhanson/Development/craft6/vendor/orchestra/workbench/src/Http/Controllers/WorkbenchController.php:39
* @route '/_workbench/user/{guard?}'
*/
user.head = (args?: { guard?: string | number } | [guard: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: user.url(args, options),
    method: 'head',
})

const WorkbenchController = { start, login, logout, user }

export default WorkbenchController