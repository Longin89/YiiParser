<?php

namespace app\services;

use app\models\Log;
use Yii;
use yii\db\ActiveQuery;

/**
 * Сервис для анализа логов
 * 
 */
class LogStatisticsService
{
    /**
     * Фильтры
     */
    private function applyFilters(ActiveQuery $query, ?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null): ActiveQuery
    {
        if ($fromDate) {
            $query->andWhere(['>=', 'DATE(request_date)', $fromDate]);
        }

        if ($toDate) {
            $query->andWhere(['<=', 'DATE(request_date)', $toDate]);
        }

        if ($os) {
            $query->andWhere(['os' => $os]);
        }

        if ($architecture) {
            $query->andWhere(['architecture' => $architecture]);
        }

        return $query;
    }

    /**
     * Сортировка
     */
    private function applySorting(ActiveQuery $query, string $sortBy = 'date_desc'): ActiveQuery
    {
        switch ($sortBy) {
            case 'date_asc':
                $query->orderBy('DATE(request_date) ASC');
                break;
            case 'count_desc':
                $query->orderBy('COUNT(*) DESC');
                break;
            case 'count_asc':
                $query->orderBy('COUNT(*) ASC');
                break;
            default:
                $query->orderBy('DATE(request_date) DESC');
        }

        return $query;
    }

    /**
     * Статистика по дням для графика
     */
    public function getDailyStats(?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null): array
    {
        $query = Log::find()
            ->select(['DATE(request_date) as date', 'COUNT(*) as count'])
            ->groupBy('DATE(request_date)')
            ->orderBy('date ASC');

        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);

        $data = $query->asArray()->all();

        $result = [];
        foreach ($data as $row) {
            $result[$row['date']] = (int)$row['count'];
        }

        return $result;
    }

    /**
     * Самые популярные браузеры
     */
    public function getTopBrowsers(?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null, int $limit = 3): array
    {
        $query = Log::find()
            ->select(['browser', 'COUNT(*) as count'])
            ->where(['is not', 'browser', null])
            ->groupBy('browser')
            ->orderBy('count DESC')
            ->limit($limit);

        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);

        return $query->asArray()->all();
    }

    /**
     * Статистика браузеров в процентах
     */
    public function getBrowserStats(?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null, int $browserLimit = 3): array
    {
        $topBrowsers = $this->getTopBrowsers($fromDate, $toDate, $os, $architecture, $browserLimit);
        $browserNames = array_column($topBrowsers, 'browser');

        if (empty($browserNames)) {
            return [];
        }

        $query = Log::find();
        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);
        $totalCount = (int)$query->count();

        if ($totalCount === 0) {
            return [];
        }

        $query = Log::find()
            ->select(['browser', 'COUNT(*) as count'])
            ->where(['in', 'browser', $browserNames])
            ->groupBy('browser')
            ->orderBy('count DESC');

        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);
        $browserStats = $query->asArray()->all();

        $result = [];
        foreach ($browserStats as $stat) {
            $percentage = ($stat['count'] / $totalCount) * 100;
            $result[] = [
                'browser' => $stat['browser'],
                'count' => (int)$stat['count'],
                'percentage' => round($percentage, 2),
            ];
        }

        return $result;
    }

    /**
     * Статистика браузеров по дням для линейного графика
     */
    public function getBrowserDailyStats(?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null, int $browserLimit = 3): array
    {
        $topBrowsers = $this->getTopBrowsers($fromDate, $toDate, $os, $architecture, $browserLimit);
        $browserNames = array_column($topBrowsers, 'browser');

        if (empty($browserNames)) {
            return ['dates' => [], 'browsers' => []];
        }

        // Общее количество запросов по дням
        $dailyTotals = $this->getDailyStats($fromDate, $toDate, $os, $architecture);
        $dates = array_keys($dailyTotals);

        if (empty($dates)) {
            return ['dates' => [], 'browsers' => []];
        }

        // Количество запросов для каждого из топ-браузеров по дням
        $query = Log::find()
            ->select(['DATE(request_date) as date', 'browser', 'COUNT(*) as count'])
            ->where(['in', 'browser', $browserNames])
            ->groupBy('DATE(request_date), browser')
            ->orderBy('date ASC');

        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);
        $rawData = $query->asArray()->all();

        $browserCounts = [];
        foreach ($browserNames as $name) {
            $browserCounts[$name] = [];
        }
        foreach ($rawData as $row) {
            $browserCounts[$row['browser']][$row['date']] = (int)$row['count'];
        }

        // Массив с долями
        $browsers = [];
        $colors = [
            'rgba(255, 99, 132, 0.5)',
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 206, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)',
            'rgba(153, 102, 255, 0.5)',
        ];
        $borderColors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 206, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
        ];

        foreach ($browserNames as $i => $name) {
            $data = [];
            foreach ($dates as $date) {
                $count = $browserCounts[$name][$date] ?? 0;
                $total = $dailyTotals[$date] ?? 1;
                $data[] = round(($count / $total) * 100, 2);
            }
            $browsers[] = [
                'name' => $name,
                'data' => $data,
                'backgroundColor' => $colors[$i % count($colors)],
                'borderColor' => $borderColors[$i % count($borderColors)],
            ];
        }

        return [
            'dates' => $dates,
            'browsers' => $browsers,
        ];
    }

    /**
     * Статистика по дням для таблицы
     */
    public function getDateStatistics(?string $fromDate = null, ?string $toDate = null, ?string $os = null, ?string $architecture = null, string $sortBy = 'date_desc'): array
    {
        $query = Log::find()
            ->select(['DATE(request_date) as date', 'COUNT(*) as count'])
            ->groupBy('DATE(request_date)');

        $query = $this->applyFilters($query, $fromDate, $toDate, $os, $architecture);
        $query = $this->applySorting($query, $sortBy);

        $rawData = $query->asArray()->all();

        $result = [];
        foreach ($rawData as $row) {
            $date = $row['date'];
            $count = (int)$row['count'];

            $topUrl = Log::find()
                ->select('url')
                ->where(['DATE(request_date)' => $date])
                ->andFilterWhere(['os' => $os])
                ->andFilterWhere(['architecture' => $architecture])
                ->addSelect(['COUNT(*) as cnt'])
                ->groupBy('url')
                ->orderBy('cnt DESC')
                ->limit(1)
                ->asArray()
                ->one();

            $topBrowser = Log::find()
                ->select('browser')
                ->where(['DATE(request_date)' => $date])
                ->andWhere(['is not', 'browser', null])
                ->andFilterWhere(['os' => $os])
                ->andFilterWhere(['architecture' => $architecture])
                ->addSelect(['COUNT(*) as cnt'])
                ->groupBy('browser')
                ->orderBy('cnt DESC')
                ->limit(1)
                ->asArray()
                ->one();

            $result[] = [
                'date' => $date,
                'count' => $count,
                'topUrl' => $topUrl ? $topUrl['url'] : '-',
                'topBrowser' => $topBrowser ? $topBrowser['browser'] : '-',
            ];
        }

        return $result;
    }
}
