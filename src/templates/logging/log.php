<!-- start log messages -->
<table id="yiiLog" width="100%" cellpadding="2" style="border-spacing:1px;font:11px Verdana, Arial, Helvetica, sans-serif;background:#EEEEEE;color:#666666;">
	<tr>
		<th style="background:black;color:white;" colspan="5">
			Application Log
		</th>
	</tr>
	<tr style="background-color: #ccc;">
		<th width="120">Timestamp</th>
		<th>Level</th>
		<th>Category</th>
		<th>Message</th>
	</tr>
	<?php $diff = number_format($data[count($data) - 1][3] - $data[0][3], 6).' sec'; ?>
	<tr>
		<td colspan="4">Total Execution Time: <?php echo $diff ?></td>
	</tr>
<?php
$colors = array(
	CLogger::LEVEL_PROFILE => '#DFFFE0',
	CLogger::LEVEL_INFO => '#FFFFDF',
	CLogger::LEVEL_WARNING => '#FFDFE5',
	CLogger::LEVEL_ERROR => '#FFC0CB',
);

$counter = 0;
foreach ($data as $index => $log)
{
	$color = ($index % 2) ? '#F5F5F5':'#FFFFFF';

	if (isset($colors[$log[1]]))
		$color=$colors[$log[1]];

	$message = '<pre>'.Blocks\HtmlHelper::encode(wordwrap($log[0])).'</pre>';
	$time = date('H:i:s.', $log[3]).sprintf('%06d',(int)(($log[3] - (int)$log[3]) * 1000000));

	echo <<<EOD
	<tr style="background:{$color}">
		<td align="center">{$time}</td>
		<td>{$log[1]}</td>
		<td>{$log[2]}</td>
		<td>{$message}</td>
	</tr>
EOD;
$counter++;
}

?>
</table>
<!-- end of log messages -->
