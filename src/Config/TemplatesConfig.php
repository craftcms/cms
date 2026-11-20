<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * Settings for how Craft finds, routes, and compiles templates.
 *
 * @since 6.0.0
 */
class TemplatesConfig extends BaseConfig
{
    /** @var list<string> The template file extensions Craft looks for when matching a template path to a file on the front end. */
    public array $defaultExtensions = ['twig', 'html', 'blade.php'];

    /** @var list<string> The filenames Craft looks for within a directory for its “index” template, when matching a template path to a file on the front end. */
    public array $indexFilenames = ['index'];

    /**
     * The path within the `templates` folder where element partial templates live.
     *
     * Partial templates render elements for [[\\CraftCms\\Cms\\Element\\Queries\\ElementQuery::render()]],
     * [[\\CraftCms\\Cms\\Element\\ElementCollection::render()]], and [[\\CraftCms\\Cms\\Element\\Element::render()]]. A partial’s
     * full path also includes the element type handle and the field layout provider’s handle, such as
     * `_partials/entry/article.twig` for an entry of type `article`.
     */
    public string $partialsPath = '_partials';

    /**
     * The template path segment prefix that marks “private” templates, which can’t be reached through a matching URL.
     *
     * Set it to an empty string to disable public template routing.
     */
    public string $privateTrigger = '_';

    /** Whether Craft’s `{% cache %}` template tag is enabled. */
    public bool $enableCaching = true;

    /**
     * The server path to the directory where Craft stores compiled Twig templates, or `null` for
     * `storage/runtime/compiled_templates`.
     */
    public ?string $compiledPath = null;

    /** @param list<string> $value */
    public function defaultExtensions(array $value): self
    {
        $this->defaultExtensions = array_values($value);

        return $this;
    }

    /** @param list<string> $value */
    public function indexFilenames(array $value): self
    {
        $this->indexFilenames = array_values($value);

        return $this;
    }

    public function partialsPath(string $value): self
    {
        $this->partialsPath = $value;

        return $this;
    }

    public function privateTrigger(string $value): self
    {
        $this->privateTrigger = $value;

        return $this;
    }

    public function enableCaching(bool $value = true): self
    {
        $this->enableCaching = $value;

        return $this;
    }

    public function compiledPath(?string $value): self
    {
        $this->compiledPath = $value;

        return $this;
    }
}
