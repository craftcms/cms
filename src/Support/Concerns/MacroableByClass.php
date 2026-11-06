<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Concerns;

/**
 * Stores Laravel macros by the class they’re registered on, so a macro registered on a subclass applies to it
 * and its subclasses, and one registered on the class using this trait applies to every subclass.
 *
 * Laravel’s `Macroable` shares one array across a class and all of its subclasses. Use this trait in the class
 * that macros should stop at, and declare `protected static $macros = []` on that class, typed
 * `array<class-string, array<string, object|callable>>`, so it doesn’t share storage with a parent class.
 * The methods have no return types, like `Macroable`’s, so subclasses that use `Macroable` again stay compatible.
 *
 * @since 6.0.0
 */
trait MacroableByClass
{
    /**
     * Register a macro for this class and its subclasses.
     *
     * @param  string  $name
     * @param  object|callable  $macro
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macros[static::class][$name] = $macro;
    }

    /**
     * Checks if a macro is registered on this class or a parent class.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return static::findMacro($name) !== null;
    }

    /**
     * Flush the macros registered on this class and its subclasses.
     *
     * @return void
     */
    public static function flushMacros()
    {
        static::$macros = array_filter(
            static::$macros,
            fn (string $class) => ! is_a($class, static::class, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Returns the macro registered under a name on this class or the closest parent class.
     */
    protected static function findMacro(string $name): object|callable|null
    {
        for ($class = static::class; $class !== false; $class = get_parent_class($class)) {
            if (isset(static::$macros[$class][$name])) {
                return static::$macros[$class][$name];
            }

            if ($class === self::class) {
                break;
            }
        }

        return null;
    }
}
