<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\FieldLayout\FieldLayoutForm;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlFragment;

class UserProfileViewModel extends ViewModel
{
    private bool $formRendered = false;

    private ?FieldLayoutForm $form = null;

    private HtmlFragment $formHtml;

    public function __construct(
        private readonly User $user,
    ) {}

    public function userId(): int
    {
        return $this->user->id;
    }

    public function email(): ?string
    {
        return $this->user->email;
    }

    /** @return array<int, array{containerId: string, tabId: string, label: string, hasErrors: bool}> */
    public function tabMenu(): array
    {
        return collect($this->form()?->getTabMenu() ?? [])
            ->map(fn (array $tab, string $containerId): array => [
                'containerId' => $containerId,
                'tabId' => $tab['tabId'],
                'label' => $tab['label'],
                'hasErrors' => $tab['class'] === 'error',
            ])
            ->values()
            ->all();
    }

    public function formFragment(): HtmlFragment
    {
        $this->form();

        return $this->formHtml ?? new HtmlFragment;
    }

    public function detailsFragment(): ?HtmlFragment
    {
        $fragment = HtmlStack::capture(fn (): string => trim(
            $this->user->getSidebarHtml(false).
            app(ContentHtml::class)->metadataHtml($this->user->getMetadata()),
        ));

        return $fragment->isEmpty() ? null : $fragment;
    }

    private function form(): ?FieldLayoutForm
    {
        if (! $this->formRendered) {
            $this->formHtml = HtmlStack::capture(function (): string {
                $this->form = $this->user->getFieldLayout()?->createForm($this->user);

                return $this->form?->render() ?? '';
            });
            $this->formRendered = true;
        }

        return $this->form;
    }
}
