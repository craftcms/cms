<?php

use craft\app\dates\DateTime;
use craft\app\helpers\AppHelper;

/* @var $panel craft\app\debug\InfoPanel */
?>
	<h1>Info</h1>

<?php

// Application Info
// -----------------------------------------------------------------------------

$values = [
	['Craft Version', $panel->data['craftVersion'].'.'.$panel->data['craftBuild']],
	['Release Date', (new DateTime('@'.$panel->data['craftReleaseDate']))->localeDate()],
	['Edition', 'Craft '.AppHelper::getEditionName($panel->data['craftEdition'])],
];

foreach ($panel->data['packages'] as $packageName => $packageVersion)
{
	$values[] = [$packageName.' Version', $packageVersion];
}

echo $this->render('../table', [
	'caption' => 'Application Info',
	'values' => $values
]);

// Plugins
// -----------------------------------------------------------------------------

array_walk($panel->data['plugins'], function(&$value)
{
	$value = [
		$value['name'],
		$value['version'],
		!empty($value['developerUrl']) ? '<a href="'.$value['developerUrl'].'">'.$value['developer'].'</a>' : $value['developer']
	];
});

echo $this->render('../table', [
	'caption' => 'Plugins',
	'headings' => ['Name', 'Version', 'Developer'],
	'values' => $panel->data['plugins']
]);

// Server Requirements Report
// -----------------------------------------------------------------------------

array_walk($panel->data['requirements'], function(&$value)
{
	if ($value['warning'])
	{
		$result = 'FAIL';

		if (!empty($value['memo']))
		{
			$result .= ' – '.$value['memo'];
		}
	}
	else
	{
		$result = 'OK';
	}

	$value = [$value['name'], $value['mandatory'] ? 'Yes' : 'No', $result];
});

echo $this->render('../table', [
	'caption' => 'Server Requirements Report',
	'headings' => ['Requirement', 'Mandatory?', 'Result'],
	'values' => $panel->data['requirements'],
]);

// PHP Info
// -----------------------------------------------------------------------------

?>

<h3>PHP Info</h3>

<div class="callout" style="background-color: #f9f9f9;">
	Jump to:
	<?php foreach (array_keys($panel->data['phpInfo']) as $i => $section): ?>
		<?php if ($i !== 0): ?>|<?php endif; ?>
		<a href="#<?= str_replace(' ', '-', $section) ?>"><?= $section ?></a>
	<?php endforeach; ?>
</div>

<?php

foreach ($panel->data['phpInfo'] as $section => $values)
{
	echo '<h4 id="'.str_replace(' ', '-', $section).'">'.$section.'</h4>';

	array_walk($values, function(&$value, $key)
	{
		if (is_array($value))
		{
			$value = implode(', ', $value);
		}

		$value = [$key, $value];
	});

	echo $this->render('../table', [
		'values' => $values
	]);
}
