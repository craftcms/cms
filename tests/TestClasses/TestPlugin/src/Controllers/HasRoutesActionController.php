<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Controllers;

class HasRoutesActionController
{
    public function __invoke(): string
    {
        return 'action';
    }
}
