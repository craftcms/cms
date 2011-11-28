{% layout '_layouts/cp' %}

{%region 'main'%}

<center><h1>Update All</h1></center>

<h1>Blocks</h1>

<?php echo BlocksHtml::beginForm('coreupdate?c=update'); ?>
<?php echo BlocksHtml::hiddenField('blocksLatestVersionNo', $model['blocksLatestVersionNo']); ?>
<?php echo BlocksHtml::hiddenField('blocksLatestBuildNo', $model['blocksLatestBuildNo']); ?>

<?php $model['blocksLatestVersionNo'].'.'.$model['blocksLatestBuildNo'] === $model['blocksClientVersionNo'].'.'.$model['blocksClientBuildNo'] ? $upToDate = true : $upToDate = false; ?>

<table border="1" cellpadding="1" cellspacing="2">
	<tr>
		<td>Your Version: <?php echo $model['blocksClientVersionNo']; ?>.<?php echo $model['blocksClientBuildNo']; ?></td>
		<td>Current Version: <?php echo $model['blocksLatestVersionNo']; ?>.<?php echo $model['blocksLatestBuildNo']; ?></td>
		<td><?php if (!$upToDate) { echo BlocksHtml::submitButton('Update'); } ?></td>
	</tr>
	<tr>
		<td colspan="3">
			<?php if (!$upToDate): ?>
			<table border="1" cellpadding="0" cellspacing="0">
				<?php foreach($model['blocksLatestCoreReleases'] as $blocksReleaseInfo): ?>
				<tr>
					<td><b>Version</b></td>
					<td><b>Release Date</b></td>
					<td><b>Release Notes</b></td>
					<td><b>Type</b></td>
				</tr>
				<tr>
					<td><?php echo $blocksReleaseInfo['version'] ?>.<?php echo $blocksReleaseInfo['build_number']; ?></td>
					<td><?php echo BlocksHtml::UnixTimeToPrettyDate($blocksReleaseInfo['release_date']); ?></td>
					<td><?php echo nl2br($blocksReleaseInfo['release_notes']); ?></td>
					<td><?php echo $blocksReleaseInfo['type']; ?></td>
				</tr>
				<?php endforeach ?>
			</table>
			<?php else: ?>
				You are running the latest version of Blocks.
			<?php endif ?>
		</td>
	</tr>

</table>
<?php echo BlocksHtml::endForm(); ?>

<h1>Plugins</h1>
<?php if (!empty($model['pluginNamesAndVersions'])): ?>
	<?php foreach($model['pluginNamesAndVersions'] as $pluginInfo): ?>
		<?php if ($pluginInfo['status'] != PluginVersionUpdateStatus::Unknown): ?>
			<table border="1" cellpadding="1" cellspacing="2">
				<tr>
					<td><b>Plugin Name:</b> <?php echo $pluginInfo['displayName']; ?></td>
					<td><b>Your Version:</b> <?php echo $pluginInfo['installedVersion']; ?></td>
					<td><b>Current Version:</b> <?php echo $pluginInfo['latestVersion']; ?></td>
					<td><b>Status:</b> <?php echo $pluginInfo['status']; ?></td>
					<td><?php if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable): ?><b>Update</b><?php endif ?></td>
				</tr>
				<?php if (isset($pluginInfo['notes']) && count($pluginInfo['notes']) > 0): ?>
				<tr>
					<td colspan="5"><b>Notes:</b><br /> <?php echo nl2br($pluginInfo['notes']); ?></td>
				</tr>
				<?php endif ?>
				<tr>
					<td colspan="5">
						<table border="1" cellpadding="0" cellspacing="0">
							<?php if (isset($pluginInfo['newerReleases']) && count($pluginInfo['newerReleases']) > 0): ?>
								<?php foreach($pluginInfo['newerReleases'] as $newerRelease): ?>
								<tr>
									<td><b>Version</b></td>
									<td><b>Release Date</b></td>
									<td><b>Release Notes</b></td>
								</tr>
								<tr>
									<td><?php echo $newerRelease['version']; ?></td>
									<td><?php echo $newerRelease['releaseDate']; ?></td>
									<td><?php echo nl2br($newerRelease['releaseNotes']); ?></td>
								</tr>
								<?php endforeach ?>
							<?php else: ?>
								<tr>
									<td>Up To Date</td>
								</tr>
							<?php endif ?>
						</table>
					</td>
				</tr>
			</table>
		<?php endif ?>
	<?php endforeach ?>
<? else: ?>
	Plugins are up to date!
<? endif ?>
{%endregion%}
