<?php
namespace Blocks;

/**
 *
 */
class InstallerService extends \CApplicationComponent
{
	/**
	 * Run the installer
	 */
	public function run()
	{
		if (Blocks::app()->isInstalled)
			throw new Exception('Blocks is already installed.');

		$modelsDir = Blocks::app()->file->set(Blocks::app()->path->modelsPath);
		$modelFiles = $modelsDir->getContents(false, '.php');
		$models = array();

		foreach ($modelFiles as $filePath)
		{
			$file = Blocks::app()->file->set($filePath);
			$fileName = $file->fileName;

			// Ignore base classes
			if (strncmp($fileName, 'Base', 4) === 0)
				continue;

			$class = __NAMESPACE__.'\\'.$fileName;
			$models[] = new $class;
		}

		// Start the transaction
		$transaction = Blocks::app()->db->beginTransaction();
		try
		{
			// Create the tables
			foreach ($models as $model)
			{
				$model->createTable();
			}

			// Create the foreign keys
			foreach ($models as $model)
			{
				$model->addForeignKeys();
			}

			// Tell Blocks that it's installed now
			Blocks::app()->isInstalled = true;

			// Populate the info table
			$info = new Info;
			$info->edition = Blocks::getEdition(false);
			$info->version = Blocks::getVersion(false);
			$info->build = Blocks::getBuild(false);
			$info->save();

			$transaction->commit();
		}
		catch (Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
