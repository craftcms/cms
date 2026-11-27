<?php

use CraftCms\Cms\Entry\Elements\Entry;
use Livewire\Component;

new class extends Component
{
    public Entry $entry;

    public int $likes = 0;

    public function like(): void
    {
        $this->likes++;
    }
};
?>

<article>entry:{{ $entry->title }}:{{ $entry->id }}:likes:{{ $likes }}:site:{{ \CraftCms\Cms\Support\Facades\Sites::getCurrentSite()->handle }}</article>
