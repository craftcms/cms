<?php

namespace craft\elements\conditions\assets;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated in 4.4.0. [[SavableConditionRule]] should be used instead.
     */
    class EditableConditionRule
    {
    }
}

class_alias(SavableConditionRule::class, EditableConditionRule::class);
