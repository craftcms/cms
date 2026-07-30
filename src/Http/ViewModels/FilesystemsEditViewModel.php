<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\HtmlFragment;
use Illuminate\Support\Collection;

/**
 * @phpstan-type FsPayload array{
 *     name: string|null,
 *     handle: string|null,
 *     hasUrls: bool,
 *     url: string|null,
 *     type: class-string<FsInterface>,
 *     settingsHtml: string|null,
 *     settingsFragment: HtmlFragment|null,
 *     showHasUrlSetting: bool,
 *     showUrlSetting: bool,
 *     ...
 * }
 * @phpstan-type FsOption array{value: class-string<FsInterface>, label: string}
 */
class FilesystemsEditViewModel extends ViewModel
{
    private readonly FsInterface $filesystem;

    /** @var Collection<class-string<FsInterface>, FsInterface> */
    private Collection $instances;

    public function __construct(
        ?FsInterface $filesystem,
        private readonly Filesystems $filesystems,
        private readonly ?string $oldHandle = null,
        public readonly bool $readOnly = false,
    ) {
        $this->filesystem = $filesystem ?? app()->make($this->fsTypes()[0]);
    }

    public function oldHandle(): ?string
    {
        return $this->oldHandle;
    }

    /** @return FsPayload */
    public function filesystem(): array
    {
        return $this->fsPayload($this->filesystem);
    }

    /** @return array<int, FsOption> */
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

    /** @return array<class-string<FsInterface>, FsPayload> */
    public function fsInstances(): array
    {
        return $this->instances()
            ->map(fn (FsInterface $fs): array => $this->fsPayload($fs))
            ->all();
    }

    /**
     * @return array<int, class-string<FsInterface>>
     */
    public function fsTypes(): array
    {
        return $this->filesystems->getAllFilesystemTypes()->values()->all();
    }

    // @TODO this should probably be its own item on SelectOptions
    /** @return list<array<string, list<array<string, mixed>>|string>> */
    public function baseUrlSuggestions(): array
    {
        return SelectOptions::getEnvSuggestions(true, fn ($value) => Str::isUrl($value));
    }

    /** @return list<array<string, list<array<string, mixed>>|string>> */
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
        return $this->instances ??= collect($this->fsTypes())->mapWithKeys(fn (string $type): array => [
            $type => $type === $this->filesystem::class ? $this->filesystem : app()->make($type),
        ]);
    }

    /** @return FsPayload */
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
