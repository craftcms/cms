<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function like(): void
    {
        $this->count++;
    }
};
?>

<div>count:{{ $count }}:site:{{ \CraftCms\Cms\Support\Facades\Sites::getCurrentSite()->handle }}</div>
