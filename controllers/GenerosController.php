<?php

namespace app\controllers;

use app\models\GenerosForm;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Definición del controlador generos.
 */
class GenerosController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'only' => ['delete'],
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['update'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $count = \Yii::$app->db
            ->createCommand('SELECT count(*) FROM generos')
            ->queryScalar();

        $pagination = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $count,
        ]);

        $filas = \Yii::$app->db
            ->createCommand('SELECT *
                               FROM generos
                           ORDER BY genero
                              LIMIT :limit
                             OFFSET :offset', [
                ':limit' => $pagination->limit,
                ':offset' => $pagination->offset,
            ])
            ->queryAll();
        return $this->render('index', [
            'filas' => $filas,
            'pagination' => $pagination,
        ]);
    }

    public function actionCreate()
    {
        $generosForm = new GenerosForm();

        if ($generosForm->load(Yii::$app->request->post()) && $generosForm->validate()) {
            Yii::$app->db->createCommand()
                ->insert('generos', $generosForm->attributes)
                ->execute();
            Yii::$app->session->setFlash('success', 'Fila insertada correctamente.');
            return $this->redirect(['generos/index']);
        }
        return $this->render('create', [
            'generosForm' => $generosForm,
        ]);
    }

    public function actionUpdate($id)
    {
        $generosForm = new GenerosForm(['attributes' => $this->buscarGenero($id)]);

        if ($generosForm->load(Yii::$app->request->post()) && $generosForm->validate()) {
            Yii::$app->db->createCommand()
                ->update('generos', $generosForm->attributes, ['id' => $id])
                ->execute();
            Yii::$app->session->setFlash('success', 'Fila modificada correctamente.');
            return $this->redirect(['generos/index']);
        }

        return $this->render('update', [
            'generosForm' => $generosForm,
            'listaGeneros' => $this->listaGeneros(),
        ]);
    }

    public function actionDelete($id)
    {
        $count = Yii::$app->db
            ->createCommand('SELECT count(*)
                               FROM peliculas
                              WHERE genero_id = :id', ['id' => $id])
            ->queryScalar();
        if ($count != 0) {
            Yii::$app->session->setFlash('error', 'Hay películas de ese género.');
        } else {
            Yii::$app->db->createCommand()
            ->delete('generos', ['id' => $id])
            ->execute();
            Yii::$app->session->setFlash('success', 'Género borrado correctamente.');
        }
        return $this->redirect(['generos/index']);
    }

    private function listaGeneros()
    {
        $generos = Yii::$app->db->createCommand('SELECT * FROM generos')->queryAll();
        $listaGeneros = [];
        foreach ($generos as $genero) {
            $listaGeneros[$genero['id']] = $genero['genero'];
        }
        return $listaGeneros;
    }

    private function buscarGenero($id)
    {
        $fila = Yii::$app->db
            ->createCommand('SELECT *
                               FROM generos
                              WHERE id = :id', [':id' => $id])
            ->queryOne();
        if ($fila === false) {
            throw new NotFoundHttpException('Esa género no existe.');
        }
        return $fila;
    }
}
