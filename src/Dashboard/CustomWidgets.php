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

        $definitions = Collection::make();
        $handles = [];

        foreach (File::files($path, hidden: true) as $file) {
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
        $handle = $metadata['handle'] ?? null;

        return new CustomWidgetDefinition(
            filename: $filename,
            handle: $handle,
            label: $metadata['label'] ?? null,
            icon: $metadata['icon'] ?? null,
            maxColspan: $metadata['maxColspan'] ?? null,
            title: $metadata['title'] ?? null,
            titleFromLabel: ! array_key_exists('title', $metadata),
            subtitle: $metadata['subtitle'] ?? null,
            showByDefault: $metadata['showByDefault'] ?? false,
            body: $parsed->getContent(),
        );
    }
}
