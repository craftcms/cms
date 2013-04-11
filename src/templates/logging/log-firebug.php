<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object')
{
	console.groupCollapsed("Application Log");

	<?php
	$environmentData = array_shift($data);

	$message = $environmentData[0];
	$level = $environmentData[1];
	$category = $environmentData[2];
	$timestamp = $environmentData[3];
	$forced = isset($environmentData[4]) ? $environmentData[4] : false;

	$cookiePos = strpos($message, '$_COOKIE');
	$serverPos = strpos($message, '$_SERVER');
	$sessionPos = strpos($message, '$_SESSION');

	$getInfo = substr($message, 0, $cookiePos);
	$nextPos = !$sessionPos ? $serverPos : $sessionPos;
	$cookieInfo = substr($message, $cookiePos, $nextPos - $cookiePos);

	$sessionInfo = false;
	if ($sessionPos)
	{
		$sessionInfo = substr($message, $sessionPos, $serverPos - $sessionPos);
	}

	$serverInfo = substr($message, $serverPos);

	Craft\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $getInfo, Craft\Craft::t('GET Info'), $forced);
	Craft\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $cookieInfo, Craft\Craft::t('COOKIE Info'), $forced);

	if ($sessionInfo)
	{
		Craft\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $sessionInfo, Craft\Craft::t('SESSION Info'), $forced);
	}

	Craft\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $serverInfo, Craft\Craft::t('SERVER Info'), $forced);

	echo "\tconsole.groupCollapsed(\"Logs\");\n";

	foreach ($data as $log)
	{
		Craft\LoggingHelper::processFireBugLogEntry($log[1], $log[3], $log[2], $log[0], null, isset($log[4]) ? $log[4] : false);
	}

	echo "\tconsole.groupEnd();\n";

	?>

	console.groupEnd();
}
/*]]>*/
</script>
