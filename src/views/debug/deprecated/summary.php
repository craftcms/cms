<?php
/** @var $panel craft\debug\DeprecatedPanel */
$count = count($panel->data);
?>
<div class="yii-debug-toolbar__block">
  <a href="<?= $panel->getUrl() ?>">
    Deprecated <span
            class="yii-debug-toolbar__label<?php if ($count !== 0): ?> yii-debug-toolbar__label_warning<?php endif; ?>"><?= $count ?></span>
  </a>
</div>
