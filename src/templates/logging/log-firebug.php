<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object')
{
	console.groupCollapsed("Application Log");

	<?php
	$environmentData = array_shift($data);

	$cookiePos = strpos($environmentData[0], '$_COOKIE');
	$serverPos = strpos($environmentData[0], '$_SERVER');
	$sessionPos = strpos($environmentData[0], '$_SESSION');

	$getInfo = substr($environmentData[0], 0, $cookiePos);
	$nextPos = !$sessionPos ? $serverPos : $sessionPos;
	$cookieInfo = substr($environmentData[0], $cookiePos, $nextPos - $cookiePos);

	$sessionInfo = false;
	if ($sessionPos)
	{
		$sessionInfo = substr($environmentData[0], $sessionPos, $serverPos - $sessionPos);
	}

	$serverInfo = substr($environmentData[0], $serverPos);

	Craft\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $getInfo, Craft\Craft::t('GET Info'));
	Craft\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $cookieInfo, Craft\Craft::t('COOKIE Info'));
	if ($sessionInfo)
	{
		Craft\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $sessionInfo, Craft\Craft::t('SESSION Info'));
	}
	Craft\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $serverInfo, Craft\Craft::t('SERVER Info'));

	echo "\tconsole.groupCollapsed(\"Logs\");\n";
	foreach ($data as $index => $log)
	{
		Craft\LoggingHelper::processFireBugLogEntry($log[1], $log[3], $log[2], $log[0]);
	}
	echo "\tconsole.groupEnd();\n";

	?>

	console.groupEnd();
}
/*]]>*/
</script>
