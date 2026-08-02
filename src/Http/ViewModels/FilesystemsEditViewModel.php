<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;

/**
 * @phpstan-type FsPayload array{
 *     name: string|null,
 *     handle: string|null,
 *     type: class-string<FsInterface>,
 * }
 * @phpstan-type FsOption array{value: class-string<FsInterface>, label: string, id: string}
 */
class FilesystemsEditViewModel extends ViewModel
{
    private readonly FsInterface $filesystem;

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
        return [
            'name' => $this->filesystem->name,
            'handle' => $this->filesystem->handle,
            'type' => $this->filesystem::class,
        ];
    }

    /** @return array<int, FsOption> */
    public function fsOptions(): array
    {
        $options = collect($this->fsTypes())
            ->map(fn (string $class): array => [
                'value' => $class,
                'label' => $class::displayName(),
                'id' => Html::id($class),
            ])
            ->all();

        return array_values(Arr::sort($options, 'label'));
    }

    /** @return array<int, class-string<FsInterface>> */
    public function fsTypes(): array
    {
        return $this->filesystems->getAllFilesystemTypes()->values()->all();
    }

    public function settingsInputNamespace(): string
    {
        return sprintf('types[%s]', $this->typeId());
    }

    public function settingsBindingScope(): string
    {
        return sprintf('types.%s', $this->typeId());
    }

    /** @return array{elements: list<array<string, mixed>>}|null */
    public function settingsForm(): ?array
    {
        return InputNamespace::with(
            $this->settingsInputNamespace(),
            fn (): ?array => $this->filesystem->getSettingsForm($this->readOnly)?->toArray(),
        );
    }

    /** @return array{types: array<string, array<string, mixed>>} */
    public function settingsValues(): array
    {
        return [
            'types' => [
                $this->typeId() => [
                    'hasUrls' => $this->filesystem->hasUrls,
                    'url' => $this->filesystem->url,
                    ...$this->filesystem->getSettings(),
                ],
            ],
        ];
    }

    /** @return array<string, string[]> */
    public function settingsErrors(): array
    {
        $settingsAttributes = array_flip([
            'hasUrls',
            'url',
            ...$this->filesystem->settingsAttributes(),
        ]);
        $errors = [];

        foreach ($this->filesystem->errors()->getMessages() as $attribute => $messages) {
            $path = isset($settingsAttributes[Str::before($attribute, '.')])
                ? sprintf('%s.%s', $this->settingsBindingScope(), $attribute)
                : $attribute;
            $errors[$path] = $messages;
        }

        return $errors;
    }

    private function typeId(): string
    {
        return Html::id($this->filesystem::class);
    }
}
