<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object' && typeof(console.groupCollapsed) == 'function')
{
	console.groupCollapsed("Application Log");

	<?php
	$environmentData = array_shift($data);

	$message = $environmentData[0];
	$level = $environmentData[1];
	$category = $environmentData[2];
	$timestamp = $environmentData[3];
	$forced = isset($environmentData[4]) ? $environmentData[4] : false;

	$cookiePos = mb_strpos($message, '$_COOKIE');
	$serverPos = mb_strpos($message, '$_SERVER');
	$sessionPos = mb_strpos($message, '$_SESSION');

	$getInfo = mb_substr($message, 0, $cookiePos);
	$nextPos = !$sessionPos ? $serverPos : $sessionPos;
	$cookieInfo = mb_substr($message, $cookiePos, $nextPos - $cookiePos);

	$sessionInfo = false;
	if ($sessionPos)
	{
		$sessionInfo = mb_substr($message, $sessionPos, $serverPos - $sessionPos);
	}

	$serverInfo = mb_substr($message, $serverPos);

	craft\app\helpers\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $getInfo, craft\app\Craft::t('app', 'GET Info'), $forced);
	craft\app\helpers\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $cookieInfo, craft\app\Craft::t('app', 'COOKIE Info'), $forced);

	if ($sessionInfo)
	{
		craft\app\helpers\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $sessionInfo, craft\app\Craft::t('app', 'SESSION Info'), $forced);
	}

	craft\app\helpers\LoggingHelper::processFireBugLogEntry($level, $timestamp, $category, $serverInfo, craft\app\Craft::t('app', 'SERVER Info'), $forced);

	echo "\tconsole.groupCollapsed(\"Logs\");\n";

	foreach ($data as $log)
	{
		craft\app\helpers\LoggingHelper::processFireBugLogEntry($log[1], $log[3], $log[2], $log[0], null, isset($log[4]) ? $log[4] : false);
	}

	echo "\tconsole.groupEnd();\n";

	?>

	console.groupEnd();
}
/*]]>*/
</script>
