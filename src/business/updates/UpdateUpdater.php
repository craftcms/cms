<?php

class UpdateUpdater
{
	private $_manifestFile;
	private $_manifestId;

	function __construct($manifestId)
	{
		$this->_manifestId = $manifestId;
		$this->_manifestFile = Blocks::app()->file->set(Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.'manifest_'.$manifestId);

		if (!$this->_manifestFile->exists)
			throw new BlocksException('Could not find the manifest file.  Update failed.');
	}

	public function start()
	{
		try
		{
			if (UpdateHelper::doFileUpdate($this->_manifestFile, UpdaterType::Updater, false))
				Blocks::app()->request->redirect(array('update/resumeupdate', 'manifestId' => $this->_manifestId, 'status' => 1));
		}
		catch (Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			UpdateHelper::rollBackFileChanges($this->_manifestFile);
			Blocks::app()->request->redirect(array('update/resumeupdate', 'manifestId' => $this->_manifestId, 'status' => 0));
		}
	}
}
