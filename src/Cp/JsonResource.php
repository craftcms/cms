<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

class JsonResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    #[\Override]
    public static $wrap;
}
