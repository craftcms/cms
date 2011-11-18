<?php

//class AssetController extends BaseController
//{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	//public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	/*public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}*/

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
/*	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
*/
	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
/*	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}
*/
	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
/*	public function actionCreate()
	{
		$model = new Asset;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (Blocks::app()->request->getPost('Asset', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('Asset');

			if($model->save())
				$this->redirect(array('view', 'id' => $model->id));
		}

		$this->render('create', array('model' => $model));
	}
*/
	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
/*	public function actionUpdate($id)
	{
		$model = $this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(Blocks::app()->request->getPost('Asset', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('Asset');
			if($model->save())
				$this->redirect(array('view','id' => $model->id));
		}

		$this->render('update', array('model' => $model));
	}
*/
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
/*	public function actionDelete($id)
	{
		if (Blocks::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if (Blocks::app()->request->isAjaxRequest)
				$this->redirect(Blocks::app()->request->getParam('returnUrl', null) !== null) ? Blocks::app()->request->getParam('returnUrl') : array('admin');
		}
		else
			throw new BlocksHttpException(400, 'Invalid request. Please do not repeat this request again.');
	}
*/
	/**
	 * Lists all models.
	 */
/*	public function actionIndex()
	{
		$dataProvider = new CActiveDataProvider('Asset');
		$this->render('index', array('dataProvider' => $dataProvider));
	}*/

	/**
	 * Manages all models.
	 */
/*	public function actionAdmin()
	{
		$model = new Asset('search');
		$model->unsetAttributes();  // clear any default values

		if(Blocks::app()->request->getPost('Asset', null) !== null)
			$model->attributes = Blocks::app()->request->getPost('Asset');

		$this->render('admin', array('model' => $model));
	}*/

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 * @return #M#M#C\Assets.model.findByPk|?*/

/*	public function loadModel($id)
	{
		$model = Assets::model()->findByPk((int)$id);

		if($model === null)
			Blocks::app()->send404();

		return $model;
	}*/

	/**
	 * Performs the AJAX validation.
	 * @param $model
	 *
	 * @internal param \the $CModel model to be validated
	 */
/*	protected function performAjaxValidation($model)
	{
		if (Blocks::app()->request->isAjaxRequest && Blocks::app()->request->getPost('ajax') === 'asset-form')
		{
			echo CActiveForm::validate($model);
			Blocks::app()->end();
		}
	}
}
*/
