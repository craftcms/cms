<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object')
{
	console.groupCollapsed("Application Log");

	<?php

	$environmentData = array_shift($data);

	$cookiePos = strpos($environmentData[0], '$_COOKIE');
	$serverPos = strpos($environmentData[0], '$_SERVER');

	$getInfo = substr($environmentData[0], 0, $cookiePos);
	$cookieInfo = substr($environmentData[0], $cookiePos, $serverPos - $cookiePos);
	$serverInfo = substr($environmentData[0], $serverPos);

	Blocks\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $getInfo, 'GET Info');
	Blocks\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $cookieInfo, 'COOKIE Info');
	Blocks\LoggingHelper::processFireBugLogEntry($environmentData[1], $environmentData[3], $environmentData[2], $serverInfo, 'SERVER Info');

	echo "\tconsole.groupCollapsed(\"Logs\");\n";
	foreach ($data as $index => $log)
	{
		Blocks\LoggingHelper::processFireBugLogEntry($log[1], $log[3], $log[2], $log[0]);
	}
	echo "\tconsole.groupEnd();\n";

	?>

	console.groupEnd();
}
/*]]>*/
</script>
