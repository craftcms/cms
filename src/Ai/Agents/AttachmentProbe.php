<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Asks a model about a file attached to a prompt, such as which color fills an image, to check that it can read that
 * kind of file.
 *
 * @internal
 *
 * @since 6.0.0
 */
class AttachmentProbe implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You check whether you can read attached files. Answer with a single word or number.';
    }
}
