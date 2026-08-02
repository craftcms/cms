<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Data;

use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

#[LiteralTypeScriptType('boolean | number | string | null | JsonValue[] | { [key: string]: JsonValue }')]
class JsonValue {}
