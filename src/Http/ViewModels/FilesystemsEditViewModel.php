<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\HtmlFragment;
use Illuminate\Support\Collection;

/**
 * @phpstan-type FsPayload array{
 *     name: string|null,
 *     handle: string|null,
 *     hasUrls: bool,
 *     url: string|null,
 *     type: class-string<FsInterface>,
 *     settingsForm: FormPayload|null,
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
        $context = new FormContext(
            namespace: 'settings',
            values: ['settings' => [
                ...$filesystem->getSettings(),
                'hasUrls' => $filesystem->hasUrls,
                'url' => $filesystem->url,
            ]],
            errors: $filesystem->errors()->getMessages(),
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: true,
        );
        $form = $filesystem->settingsForm($context);
        $settingsHtml = fn (): string => (string) ($this->readOnly
            ? $filesystem->getReadOnlySettingsHtml()
            : $filesystem->getSettingsHtml());

        $legacy = $filesystem->hasLegacySettingsHtml();

        return [
            ...$filesystem->toArray(),
            'type' => $filesystem::class,
            'settingsForm' => $form === null ? null : app(FormResolver::class)->resolve($form, $context),
            'settingsHtml' => $form === null && ! $legacy ? $settingsHtml() : null,
            'settingsFragment' => $form === null && $legacy ? HtmlStack::capture($settingsHtml) : null,
            'showHasUrlSetting' => $filesystem->getShowHasUrlSetting(),
            'showUrlSetting' => $filesystem->getShowUrlSetting(),
        ];
    }
}
