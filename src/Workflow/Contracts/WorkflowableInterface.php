<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Workflow\Models\Workflow;

interface WorkflowableInterface extends ElementInterface
{
    public static function hasDrafts(): true;

    public function workflow(): ?Workflow;
}
