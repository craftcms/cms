<?php
/** @var $panel craft\app\debug\InfoPanel */
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        Craft
        <span class="yii-debug-toolbar__label"><?= $panel->data['craftVersion'] ?></span>
        PHP
        <span class="yii-debug-toolbar__label"><?= $panel->data['phpVersion'] ?></span>
    </a>
</div>
