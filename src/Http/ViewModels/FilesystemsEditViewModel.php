<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;

class FilesystemsEditViewModel extends ViewModel
{
    private readonly FsInterface $filesystem;

    /** @var Collection<class-string<FsInterface>, FsInterface> */
    private Collection $instances;

    public function __construct(
        ?FsInterface $filesystem,
        private readonly Filesystems $filesystems,
        private readonly ?string $oldHandle = null,
        private readonly bool $readOnly = false,
    ) {
        $this->filesystem = $filesystem ?? app()->make($this->fsTypes()->first());
    }

    public function oldHandle(): ?string
    {
        return $this->oldHandle;
    }

    public function filesystem(): array
    {
        return $this->fsPayload($this->filesystem);
    }

    /** @return array<int, array{value: class-string<FsInterface>, label: string}> */
    public function fsOptions(): array
    {
        $options = $this->instances()
            ->map(fn (FsInterface $fs): array => [
                'value' => $fs::class,
                'label' => $fs::displayName(),
            ])
            ->values()
            ->all();

        return array_values(Arr::sort($options, 'label'));
    }

    /** @return array<class-string<FsInterface>, array> */
    public function fsInstances(): array
    {
        return $this->instances()
            ->map(fn (FsInterface $fs): array => $this->fsPayload($fs))
            ->all();
    }

    /** @return Collection<int, class-string<FsInterface>> */
    public function fsTypes(): Collection
    {
        return $this->filesystems->getAllFilesystemTypes();
    }

    // @TODO this should probably be its own item on SelectOptions
    public function baseUrlSuggestions(): array
    {
        return SelectOptions::getEnvSuggestions(true, fn ($value) => Str::isUrl($value));
    }

    public function basePathSuggestions(): array
    {
        return SelectOptions::getEnvSuggestions(true);
    }

    /**
     * One instance per filesystem type; the type being edited is represented
     * by the actual filesystem so its slot reflects the saved settings.
     *
     * @return Collection<class-string<FsInterface>, FsInterface>
     */
    private function instances(): Collection
    {
        return $this->instances ??= $this->fsTypes()->mapWithKeys(fn (string $type): array => [
            $type => $type === $this->filesystem::class ? $this->filesystem : app()->make($type),
        ]);
    }

    private function fsPayload(FsInterface $filesystem): array
    {
        $settingsHtml = fn (): string => (string) ($this->readOnly
            ? $filesystem->getReadOnlySettingsHtml()
            : $filesystem->getSettingsHtml());

        $legacy = $filesystem->hasLegacySettingsHtml();

        return [
            ...$filesystem->toArray(),
            'type' => $filesystem::class,
            'settingsHtml' => $legacy ? null : $settingsHtml(),
            // Legacy settings may register asset bundles and inline JS, so they
            // are captured as a fragment and rendered as an isolated HTML island
            'settingsFragment' => $legacy ? HtmlStack::capture($settingsHtml) : null,
            'showHasUrlSetting' => $filesystem->getShowHasUrlSetting(),
            'showUrlSetting' => $filesystem->getShowUrlSetting(),
        ];
    }
}
