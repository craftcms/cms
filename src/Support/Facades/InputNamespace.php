<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null get()
 * @method static \CraftCms\Cms\View\InputNamespace set(string|null $namespace)
 * @method static mixed with(string|null $namespace, callable $callback)
 * @method static string namespaceInputs(callable|string $html, string|null $namespace = null, bool $otherAttributes = true, bool $withClasses = false)
 * @method static string namespaceInputName(string $inputName, string|null $namespace = null)
 * @method static string namespaceId(string $inputId, string|null $namespace = null)
 *
 * @see \CraftCms\Cms\View\InputNamespace
 */
class InputNamespace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\InputNamespace::class;
    }
}
