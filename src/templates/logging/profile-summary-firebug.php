<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object' && typeof console.groupCollapsed == 'function')
{
	console.groupCollapsed("Profiling Summary Report");
	console.log("Time:   <?php echo sprintf('%0.5f', Craft\Craft::getLogger()->getExecutionTime()); ?>s\n");
	console.log("Memory: <?php echo number_format(Craft\Craft::getLogger()->getMemoryUsage() / 1024); ?>Kb\n");
	console.log("Total Queries: <?php echo is_array($data) ? count($data) : '0'; ?>\n");
	console.log(" count   total   average    min      max   ");

	<?php
	foreach ($data as $index => $entry)
	{
		$proc    = CJavaScript::quote($entry[0]);
		$count   = sprintf('%5d',$entry[1]);
		$min     = sprintf('%0.5f',$entry[2]);
		$max     = sprintf('%0.5f',$entry[3]);
		$total   = sprintf('%0.5f',$entry[4]);
		$average = sprintf('%0.5f',$entry[4]/$entry[1]);
		echo "\tconsole.log(\" $count  $total  $average  $min  $max    {$proc}\");\n";
	}
	?>

	console.groupEnd();
}
/*]]>*/
</script>
