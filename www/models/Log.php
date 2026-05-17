<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property string $ip_address IP адрес
 * @property string $request_date Дата и время запроса
 * @property string $url URL
 * @property string $user_agent User-Agent
 * @property string|null $os Операционная система
 * @property string|null $architecture Архитектура (x86 или x64)
 * @property string|null $browser Браузер
 * @property string $created_at
 */
class Log extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%logs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip_address', 'request_date', 'url', 'user_agent'], 'required'],
            [['request_date', 'created_at'], 'safe'],
            [['user_agent'], 'string'],
            [['ip_address'], 'string', 'max' => 45],
            [['url'], 'string', 'max' => 2048],
            [['os'], 'string', 'max' => 100],
            [['architecture'], 'string', 'max' => 20],
            [['browser'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip_address' => 'IP адрес',
            'request_date' => 'Дата и время запроса',
            'url' => 'URL',
            'user_agent' => 'User-Agent',
            'os' => 'ОС',
            'architecture' => 'Архитектура',
            'browser' => 'Браузер',
            'created_at' => 'Создано',
        ];
    }

    /**
     * Получить все уникальные операционные системы
     */
    public static function getAllOperatingSystems(): array
    {
        return Yii::$app->db->createCommand(
            "SELECT DISTINCT os FROM {{logs}} WHERE os IS NOT NULL ORDER BY os"
        )->queryColumn();
    }

    /**
     * Получить все уникальные архитектуры
     */
    public static function getAllArchitectures(): array
    {
        return Yii::$app->db->createCommand(
            "SELECT DISTINCT architecture FROM {{logs}} WHERE architecture IS NOT NULL ORDER BY architecture"
        )->queryColumn();
    }

    /**
     * Получить все уникальные браузеры
     */
    public static function getAllBrowsers(): array
    {
        return Yii::$app->db->createCommand(
            "SELECT DISTINCT browser FROM {{logs}} WHERE browser IS NOT NULL ORDER BY browser"
        )->queryColumn();
    }
}
