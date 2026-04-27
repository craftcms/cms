<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Console\Command;

trait ResolvesElementById
{
    private function resolveElementById(int $id): ElementInterface|int
    {
        if ($id < 1) {
            $this->components->error("Invalid element ID: $id");

            return Command::INVALID;
        }

        $element = Elements::getElementById(
            $id,
            criteria: [
                'siteId' => '*',
                'unique' => true,
                'trashed' => null,
                'drafts' => null,
                'provisionalDrafts' => null,
                'revisions' => null,
            ],
        );

        if (! $element) {
            $this->components->error("Invalid element ID: $id");

            return Command::FAILURE;
        }

        return $element;
    }
}
