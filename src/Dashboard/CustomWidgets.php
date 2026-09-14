<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard;

use CraftCms\Cms\Dashboard\Data\CustomWidgetDefinition;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Finder\SplFileInfo;
use UnexpectedValueException;

#[Singleton]
class CustomWidgets
{
    /** @var Collection<string, CustomWidgetDefinition>|null */
    private ?Collection $definitions = null;

    private readonly FrontMatterParser $frontMatterParser;

    public function __construct()
    {
        $this->frontMatterParser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
    }

    /**
     * @return Collection<string, CustomWidgetDefinition>
     */
    public function all(): Collection
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $path = resource_path('widgets');

        if (! is_dir($path)) {
            return $this->definitions = Collection::make();
        }

        try {
            $files = File::files($path, hidden: true);
        } catch (UnexpectedValueException) {
            // the directory was removed between the is_dir() check and now
            return $this->definitions = Collection::make();
        }

        $definitions = Collection::make();
        $handles = [];

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $definition = $this->definition($file);

            if ($definition->handle !== null) {
                $handle = strtolower($definition->handle);

                if (isset($handles[$handle])) {
                    throw new InvalidArgumentException("Custom widget files [{$handles[$handle]}] and [{$definition->filename}] have the same handle.");
                }

                $handles[$handle] = $definition->filename;
            }

            $definitions->put($definition->id, $definition);
        }

        return $this->definitions = $definitions;
    }

    public function find(string $id): ?CustomWidgetDefinition
    {
        return $this->all()->get($id);
    }

    public function fromType(string $type): ?CustomWidgetDefinition
    {
        return $this->all()->first(fn (CustomWidgetDefinition $definition) => $definition->type() === $type);
    }

    private function definition(SplFileInfo $file): CustomWidgetDefinition
    {
        $filename = $file->getFilename();
        $contents = File::get($file->getPathname());

        $parsed = $this->frontMatterParser->parse($contents);
        $metadata = (array) $parsed->getFrontMatter();

        $string = function (string $property) use ($filename, $metadata): ?string {
            $value = $metadata[$property] ?? null;

            if ($value === null || is_scalar($value)) {
                return $value === null ? null : (string) $value;
            }

            throw new InvalidArgumentException("Custom widget file [$filename] frontmatter property [$property] must be a string or null.");
        };

        $maxColspan = $metadata['maxColspan'] ?? null;

        if ($maxColspan !== null) {
            $maxColspan = filter_var($maxColspan, FILTER_VALIDATE_INT);

            if ($maxColspan === false) {
                throw new InvalidArgumentException("Custom widget file [$filename] frontmatter property [maxColspan] must be an integer between 1 and 4, or null.");
            }
        }

        $showByDefault = filter_var($metadata['showByDefault'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($showByDefault === null) {
            throw new InvalidArgumentException("Custom widget file [$filename] frontmatter property [showByDefault] must be a boolean.");
        }

        return new CustomWidgetDefinition(
            filename: $filename,
            handle: $string('handle'),
            label: $string('label'),
            icon: $string('icon'),
            maxColspan: $maxColspan,
            title: $string('title'),
            titleFromLabel: ! array_key_exists('title', $metadata),
            subtitle: $string('subtitle'),
            showByDefault: $showByDefault,
            body: $parsed->getContent(),
        );
    }
}
