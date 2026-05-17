<?php

namespace app\controllers;

use app\models\Log;
use app\services\LogStatisticsService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    private LogStatisticsService $statisticsService;

    public function __construct($id, $module, LogStatisticsService $statisticsService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->statisticsService = $statisticsService;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
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
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Главная страница со статистикой по логам
     *
     * @return string
     */
    public function actionIndex()
    {
        // Получить параметры фильтра из запроса
        $filters = $this->getFilters();

        // Получить статистику из сервиса
        $data = [
            'dateStats' => $this->statisticsService->getDateStatistics($filters['fromDate'], $filters['toDate'], $filters['os'], $filters['architecture'], $filters['sortBy']),
            'dailyStats' => $this->statisticsService->getDailyStats($filters['fromDate'], $filters['toDate'], $filters['os'], $filters['architecture']),
            'topBrowsers' => $this->statisticsService->getTopBrowsers($filters['fromDate'], $filters['toDate'], $filters['os'], $filters['architecture'], 3),
            'browserStats' => $this->statisticsService->getBrowserStats($filters['fromDate'], $filters['toDate'], $filters['os'], $filters['architecture'], 3),
            'browserDailyStats' => $this->statisticsService->getBrowserDailyStats($filters['fromDate'], $filters['toDate'], $filters['os'], $filters['architecture'], 3),
            'allOs' => Log::getAllOperatingSystems(),
            'allArchitectures' => Log::getAllArchitectures(),
            'filters' => $filters,
        ];

        return $this->render('index', $data);
    }

    /**
     * Получить параметры фильтра из запроса
     * 
     * @return array
     */
    private function getFilters(): array
    {
        return [
            'fromDate' => Yii::$app->request->get('from_date'),
            'toDate' => Yii::$app->request->get('to_date'),
            'os' => Yii::$app->request->get('os'),
            'architecture' => Yii::$app->request->get('architecture'),
            'sortBy' => Yii::$app->request->get('sort', 'date_desc'),
        ];
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
