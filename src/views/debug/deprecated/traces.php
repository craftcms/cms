<?php

use craft\app\models\DeprecationError;

/* @var $log DeprecationError */
/* @var $panel craft\app\debug\DeprecatedPanel */
?>
	<h1><?= $log->key ?></h1>

<?php

echo $this->render('../table', [
	'caption' => 'Error Info',
	'values' => [
		['Message', $log->message],
		['Origin', $log->getOrigin()],
		['Last Occurance', $log->lastOccurrence->localeDate().' '.$log->lastOccurrence->localeTime()],
	]
]);



$values = [];
$totalTraces = count($log->traces);

foreach ($log->traces as $i => $trace)
{
	if ($i === 0)
	{
		$info = '<strong>Deprecation error:</strong> '.$log->message;
	}
	else if (!empty($trace['template']))
	{
		$info = '<strong>Template:</strong> '.$trace['template'];
	}
	else
	{
		$info = (!empty($trace['objectClass']) || !empty($trace['class']) ? str_replace('\\', '\<wbr>', ($trace['objectClass'] ?: $trace['class'])).'::<wbr>' : '').$trace['method'].'('.$trace['args'].')';
	}

	if (!empty($trace['file']))
	{
		$info .= '<br><strong>From:</strong> '.str_replace('/', '/<wbr>', $trace['file']).' ('.$trace['line'].')';
	}

	$values[] = [
		($totalTraces - $i),
		$info
	];
}

echo $this->render('../table', [
	'columnStyles' => ['width: 5%; text-align: center;', ''],
	'caption' => 'Stack Trace',
	'values' => $values
]);
