<?php

use craft\app\models\DeprecationError;

/* @var $panel craft\app\debug\DeprecatedPanel */
?>
	<h1>Deprecation Errors</h1>

<?php

array_walk($panel->data, function(&$log)
{
	$log = DeprecationError::create($log);
});

echo $this->render('logtable', [
	'panel' => $panel,
	'caption' => 'This Request',
	'logs' => $panel->data
]);

?>

<h3>All Logged Deprecation Errors</h3>
<p><a href="<?= $panel->getUrl() ?>&clear=1">Clear All</a></p>

<?php

echo $this->render('logtable', [
	'panel' => $panel,
	'logs' => Craft::$app->getDeprecator()->getLogs(null)
]);
