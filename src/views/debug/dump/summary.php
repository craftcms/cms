<?php
/** @var craft\debug\DumpPanel $panel */
$count = count($panel->data);
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        Dumps <span
                class="yii-debug-toolbar__label"><?= $count ?></span>
    </a>
</div>
