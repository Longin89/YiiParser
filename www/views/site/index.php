<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $dateStats array */
/* @var $dailyStats array */
/* @var $topBrowsers array */
/* @var $browserStats array */
/* @var $allOs array */
/* @var $allArchitectures array */
/* @var $filters array */

$this->title = 'Анализ логов Nginx';
?>
<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', ['position' => View::POS_END]);
$this->registerJsFile('/js/charts.js', ['position' => View::POS_END, 'depends' => 'app\assets\AppAsset']);
?>

<div class="site-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="filters-section">
        <h3>Фильтры</h3>
        <form method="get" class="form-filters">
            <div class="form-group row">
                <div class="col-md-2">
                    <label for="from-date">От:</label>
                    <input type="date" id="from-date" name="from_date" class="form-control"
                        value="<?= Html::encode($filters['fromDate'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="to-date">До:</label>
                    <input type="date" id="to-date" name="to_date" class="form-control"
                        value="<?= Html::encode($filters['toDate'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="os-filter">ОС:</label>
                    <select id="os-filter" name="os" class="form-control">
                        <option value="">Все ОС</option>
                        <?php foreach ($allOs as $osName): ?>
                            <option value="<?= Html::encode($osName) ?>"
                                <?= (($filters['os'] ?? '') === $osName) ? 'selected' : '' ?>>
                                <?= Html::encode($osName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="arch-filter">Архитектура:</label>
                    <select id="arch-filter" name="architecture" class="form-control">
                        <option value="">Все архитектуры</option>
                        <?php foreach ($allArchitectures as $arch): ?>
                            <option value="<?= Html::encode($arch) ?>"
                                <?= (($filters['architecture'] ?? '') === $arch) ? 'selected' : '' ?>>
                                <?= Html::encode($arch) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2" style="padding-top: 31px;">
                    <button type="submit" class="btn btn-primary">Применить фильтры</button>
                </div>
            </div>
        </form>
    </div>

    <div class="charts-section row" style="margin-bottom: 30px;">
        <div class="col-md-6">
            <h3>Количество запросов по дням</h3>
            <canvas id="requestsChart"
                data-dates="<?= Html::encode(json_encode(array_keys($dailyStats))) ?>"
                data-counts="<?= Html::encode(json_encode(array_values($dailyStats))) ?>">
            </canvas>
        </div>
        <div class="col-md-6">
            <h3>Распределение браузеров (топ 3)</h3>
            <canvas id="browserChart"
                data-dates="<?= Html::encode(json_encode($browserDailyStats['dates'])) ?>"
                data-browsers-json="<?= Html::encode(json_encode($browserDailyStats['browsers'])) ?>">
            </canvas>
        </div>
    </div>

    <div class="table-section">
        <h3>Статистика по дням</h3>
        <div class="sort-controls" style="margin-bottom: 10px;">
            <label>Сортировать:</label>
            <a href="<?= Url::current(['sort' => 'date_asc']) ?>"
                class="btn btn-sm <?= ($filters['sortBy'] === 'date_asc') ? 'btn-primary' : 'btn-default' ?>">
                Дата ↑
            </a>
            <a href="<?= Url::current(['sort' => 'date_desc']) ?>"
                class="btn btn-sm <?= ($filters['sortBy'] === 'date_desc') ? 'btn-primary' : 'btn-default' ?>">
                Дата ↓
            </a>
            <a href="<?= Url::current(['sort' => 'count_desc']) ?>"
                class="btn btn-sm <?= ($filters['sortBy'] === 'count_desc') ? 'btn-primary' : 'btn-default' ?>">
                Кол-во ↓
            </a>
            <a href="<?= Url::current(['sort' => 'count_asc']) ?>"
                class="btn btn-sm <?= ($filters['sortBy'] === 'count_asc') ? 'btn-primary' : 'btn-default' ?>">
                Кол-во ↑
            </a>
        </div>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Число запросов</th>
                    <th>Самый популярный URL</th>
                    <th>Самый популярный браузер</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dateStats)): ?>
                    <?php foreach ($dateStats as $stat): ?>
                        <tr>
                            <td><?= Html::encode($stat['date']) ?></td>
                            <td><?= $stat['count'] ?></td>
                            <td>
                                <small><?= Html::encode(mb_substr($stat['topUrl'], 0, 50)) ?><?= mb_strlen($stat['topUrl']) > 50 ? '...' : '' ?></small>
                            </td>
                            <td><?= Html::encode($stat['topBrowser']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Нет данных</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>