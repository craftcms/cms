<?php

namespace craft\elements\conditions\entries;

/**
 * @deprecated in 4.4.0. [[SavableConditionRule]] should be used instead.
 * @phpstan-ignore-next-line
 */
if (false) {
    class EditableConditionRule
    {
    }
}

class_alias(SavableConditionRule::class, EditableConditionRule::class);
