<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\SupplierSearch;
use app\models\Supplier;
use yii\helpers\Html;
use yii\data\Pagination;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SupplierSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index',[
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionExport()
    {
        $searchModel = new SupplierSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());
        
        $csv = $this->convertSupplierToCSV($dataProvider);

        return \Yii::$app->response->sendContentAsFile($csv, 'sample.csv', [
           'mimeType' => 'application/csv', 
           'inline'   => false
        ]);
    }

    private function convertSupplierToCSV($dp)
    {
        $fp = fopen('php://temp', 'w');

        /* 
         * Write a header of csv file
         */
        $headers = [
            'id',
            'name',
            'code',
            't_status',
        ];

        $sp = new Supplier();
        foreach($headers as $header) {
            $row[] = $sp->getAttributeLabel($header);
        }
        fputcsv($fp,$row);

        $count = $dp->query->count();
        $pagination = new Pagination(['totalCount' => $count]);

        while($models = $dp->query->offset($pagination->offset)->limit($pagination->limit)->all()) {
            foreach($models as $model) {
                $row = [];
                foreach($headers as $head) {
                    $row[] = Html::getAttributeValue($model,$head);
                }
                fputcsv($fp,$row);
            }

            unset($models);
            $pagination = $dp->getPagination();
            $pagination->setPage($pagination->getPage()+1);
            $dp->setPagination($pagination);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);
        return $content;
    }
}
