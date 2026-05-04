<?php

namespace craft\base;

trait FieldEventConstants
{
    public const string EVENT_DEFINE_INPUT_HTML = 'defineInputHtml';

    public const string EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    public const string EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    public const string EVENT_AFTER_MERGE_INTO = 'afterMergeInto';

    public const string EVENT_AFTER_MERGE_FROM = 'afterMergeFrom';

    public const string EVENT_BEFORE_SAVE = 'beforeSave';

    public const string EVENT_AFTER_SAVE = 'afterSave';

    public const string EVENT_BEFORE_DELETE = 'beforeDelete';

    public const string EVENT_BEFORE_APPLY_DELETE = 'beforeApplyDelete';

    public const string EVENT_AFTER_DELETE = 'afterDelete';

    public const string EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';

    public const string EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';

    public const string EVENT_AFTER_ELEMENT_PROPAGATE = 'afterElementPropagate';

    public const string EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';

    public const string EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';

    public const string EVENT_BEFORE_ELEMENT_RESTORE = 'beforeElementRestore';

    public const string EVENT_AFTER_ELEMENT_RESTORE = 'afterElementRestore';
}
