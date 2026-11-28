<?php

use Livewire\Component;

new class extends Component
{
    public function rendering($view): void
    {
        $view->response(function ($response): void {
            $response->setStatusCode(202);
            $response->headers->set('X-Livewire-Page', 'status');
            $response->headers->setCookie(cookie('livewire-page', 'status'));
        });
    }
};
?>

<div>status</div>
