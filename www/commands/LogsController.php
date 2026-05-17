<?php

namespace app\commands;

use app\components\LogParser;
use app\models\Log;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команда для загрузки логов в БД
 */
class LogsController extends Controller
{

    /**
     * Загружаем логи из файла в БД
     *
     * @param string $file Путь к файлу логов
     * @param int $batch Размер батча для вставки
     * @return int
     */

    public function actionLoad($file, $batch = 100)
    {
        if (!file_exists($file)) {
            $this->stderr("Ошибка: Файл '{$file}' не найден\n");
            return ExitCode::DATAERR;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->stderr("Ошибка: Не удалось открыть файл '{$file}'\n");
            return ExitCode::DATAERR;
        }

        $logs = [];
        $count = 0;
        $errors = 0;

        $this->stdout("Начало загрузки логов\n");

        while (($line = fgets($handle)) !== false) {
            $logData = LogParser::parseLog($line);

            if ($logData === null) {
                $errors++;
                continue;
            }

            $logs[] = $logData;

            // Накопили нужный батч - вставляем
            if (count($logs) >= $batch) {
                $count += $this->insertLogs($logs);
                $logs = [];
                $this->stdout("Обработано: $count записей\n");
            }
        }

        // Вставляем оставшиеся логи
        if (!empty($logs)) {
            $count += $this->insertLogs($logs);
        }

        // Закрываем файл
        fclose($handle);

        $this->stdout("Загрузка завершена!\n");
        $this->stdout("Всего записей загружено: $count\n");
        if ($errors > 0) {
            $this->stdout("Ошибок: $errors\n");
        }

        return ExitCode::OK;
    }


    /**
     * Очистить таблицу логов
     *
     * @return int
     */

    public function actionClear()
    {
        $this->stdout("Внимание! Это удалит все логи из БД!\n");
        $response = $this->prompt("Вы уверены? (yes/no):");

        if ($response === 'yes') {
            Log::deleteAll();
            $this->stdout("Таблица логов очищена\n");
            return ExitCode::OK;
        }

        return ExitCode::OK;
    }

    /**
     * Подсчитать общее количество логов в БД
     *
     * @return int
     */

    public function actionCount()
    {
        $count = Log::find()->count();
        $this->stdout("Всего записей в Базе: $count\n");
        return ExitCode::OK;
    }


    /**
     * Вставляем массив логов в БД
     *
     * @param array $logsData Массив логов для вставки
     * @return int Количество вставленных записей
     */

    private function insertLogs(array $logsData)
    {
        if (empty($logsData)) {
            return 0;
        }

        $db = Log::getDb();
        $cols = array_keys($logsData[0]);

        return $db->createCommand()->batchInsert(Log::tableName(), $cols, $logsData)->execute();
    }
}
