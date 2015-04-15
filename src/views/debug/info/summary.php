<?php
/* @var $panel craft\app\debug\InfoPanel */
?>
<div class="yii-debug-toolbar-block">
	<a href="<?= $panel->getUrl() ?>">
		Craft
		<span class="label"><?= $panel->data['craftVersion'].'.'.$panel->data['craftBuild'] ?></span>
		PHP
		<span class="label"><?= $panel->data['phpVersion'] ?></span>
	</a>
</div>
