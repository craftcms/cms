<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Scoped;
use Stringable;

#[Scoped]
class InputNamespace
{
    private ?string $namespace = null;

    /**
     * Returns the active namespace.
     *
     * This is the default namespace that will be used when {@see namespaceInputs()}, {@see namespaceInputName()},
     * and {@see namespaceId()} are called, if their $namespace arguments are null.
     */
    public function get(): ?string
    {
        return $this->namespace;
    }

    /**
     * Sets the active namespace.
     *
     * This is the default namespace that will be used when {@see namespaceInputs()}, {@see namespaceInputName()},
     * and {@see namespaceId()} are called, if their `$namespace` arguments are null.
     *
     * @param  string|null  $namespace  The new namespace. Set to null to remove the namespace.
     */
    public function set(?string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Temporarily sets the active namespace for the duration of the given callback.
     *
     * The previous namespace will be restored after the callback has executed,
     * even if an exception is thrown.
     *
     * @param  string|null  $namespace  The namespace to set during the callback execution.
     * @param  callable  $callback  The callback to execute. Receives the `InputNamespace` instance as its argument.
     * @return mixed The value returned by the callback.
     */
    public function with(?string $namespace, callable $callback): mixed
    {
        $original = $this->namespace;

        try {
            $this->set($namespace);

            return $callback($this);
        } finally {
            $this->set($original);
        }
    }

    /**
     * Renames HTML input names so they belong to a namespace.
     *
     * This method will go through the passed-in $html looking for `name=` attributes, and renaming their values such
     * that they will live within the passed-in $namespace (or the [[get()|active namespace]]).
     * By default, any `id=`, `for=`, `list=`, `data-target=`, `data-reverse-target=`, and `data-target-prefix=`
     * attributes will get namespaced as well, by prepending the namespace and a hyphens to their values.
     * For example, the following HTML:
     *
     * ```html
     * <label for="title">Title</label>
     * <input type="text" name="title" id="title">
     * ```
     *
     * would become this, if it were namespaced with “foo”:
     *
     * ```html
     * <label for="foo-title">Title</label>
     * <input type="text" name="foo[title]" id="foo-title">
     * ```
     *
     * Attributes that are already namespaced will get double-namespaced. For example, the following HTML:
     *
     * ```html
     * <label for="bar-title">Title</label>
     * <input type="text" name="bar[title]" id="bar-title">
     * ```
     *
     * would become:
     *
     * ```html
     * <label for="foo-bar-title">Title</label>
     * <input type="text" name="foo[bar][title]" id="foo-bar-title">
     * ```
     *
     * When a callable is passed to `$html`, the namespace will be set via
     * {@see set()} before the callable is executed, in time for any JavaScript code that needs to be
     * registered by the callable.
     *
     * ```php
     * $html = InputNamespace::namespaceInputs(
     *     fn () => Html::textInput('title'),
     *     'widget-settings',
     * );
     * ```
     *
     * @param  callable|string|Stringable  $html  The HTML code, or a callable that returns the HTML code
     * @param  string|null  $namespace  The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @param  bool  $otherAttributes  Whether `id`, `for`, and other attributes should be namespaced (in addition to `name`)
     * @param  bool  $withClasses  Whether class names should be namespaced as well (affects both `class` attributes and
     *                             class name CSS selectors within `<style>` tags). This will only have an effect if `$otherAttributes` is `true`.
     * @return string The HTML with namespaced attributes
     */
    public function namespaceInputs(callable|string|Stringable $html, ?string $namespace = null, bool $otherAttributes = true, bool $withClasses = false): string
    {
        if (is_callable($html)) {
            // If no namespace was passed in, just return the callable response directly.
            // No need to namespace it via the currently-set namespace in this case; if there is one, it should get applied later on.
            if ($namespace === null) {
                return (string) $html();
            }

            $oldNamespace = $this->namespace;
            $this->set($this->namespaceInputName($namespace));
            try {
                $response = $this->namespaceInputs((string) $html(), $namespace, $otherAttributes, $withClasses);
            } finally {
                $this->set($oldNamespace);
            }

            return $response;
        }

        $html = (string) $html;

        if ($html === '') {
            return $html;
        }

        $namespace ??= $this->namespace;

        // If there's no active namespace, we're done here
        if ($namespace === null) {
            return $html;
        }

        return $otherAttributes
            ? Html::namespaceHtml($html, $namespace, $withClasses)
            : Html::namespaceInputs($html, $namespace);
    }

    /**
     * Namespaces an input name.
     *
     * This method applies the same namespacing treatment that {@see namespaceInputs()} does to `name=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param  string  $inputName  The input name that should be namespaced.
     * @param  string|null  $namespace  The namespace. Defaults to the [[get()|active namespace]].
     * @return string The namespaced input name.
     */
    public function namespaceInputName(string $inputName, ?string $namespace = null): string
    {
        if ($inputName === '') {
            return $inputName;
        }

        $namespace ??= $this->namespace;

        if ($namespace === null) {
            return $inputName;
        }

        return Html::namespaceInputName($inputName, $namespace);
    }

    /**
     * Namespaces an input ID.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `id=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param  string  $inputId  The input ID that should be namespaced.
     * @param  string|null  $namespace  The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @return string The namespaced input ID.
     */
    public function namespaceId(string $inputId, ?string $namespace = null): string
    {
        if ($inputId === '') {
            return $inputId;
        }

        $namespace ??= $this->namespace;

        if ($namespace === null) {
            return Html::id($inputId);
        }

        return Html::namespaceId($inputId, $namespace);
    }
}
