<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%logs}}`.
 */
class m260516_084311_create_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%logs}}', [
            'id' => $this->primaryKey(),
            'ip_address' => $this->string(45)->notNull()->comment('IP адрес'),
            'request_date' => $this->dateTime()->notNull()->comment('Дата и время запроса'),
            'url' => $this->string(2048)->notNull()->comment('URL'),
            'user_agent' => $this->text()->notNull()->comment('User-Agent'),
            'os' => $this->string(100)->comment('Операционная система'),
            'architecture' => $this->string(20)->comment('Архитектура (x86 или x64)'),
            'browser' => $this->string(100)->comment('Браузер'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_request_date', '{{%logs}}', 'request_date');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%logs}}');
    }
}
