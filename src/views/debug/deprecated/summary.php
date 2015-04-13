<?php
/* @var $panel craft\app\debug\DeprecatedPanel */
$count = count($panel->data);
?>
<div class="yii-debug-toolbar-block">
	<a href="<?= $panel->getUrl() ?>">
		Deprecated
		<span class="label<?php if ($count !== 0): ?> label-warning<?php endif; ?>"><?= $count ?></span>
	</a>
</div>
