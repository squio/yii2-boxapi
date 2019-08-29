<?php

use yii\db\Migration;
use yii\db\Schema;
/**
 * Class m190613_214736_box_user_table
 *
 * Apply this migration with
 *   ./yii migrate -p vendor/squio/boxapi/src/migrations
 */
class m190613_214736_box_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable(
            '{{%box_user}}',
            [
                'id'=> $this->primaryKey(),
                'user_id'=> $this->integer(11),
                'token'=> $this->binary(),      // to allow encrypted data
            ],
            $tableOptions
        );
        $this->createIndex('user_id', '{{%box_user}}','user_id',0);
        $this->addForeignKey('fk_box_user_user_id', '{{%box_user}}', 'user_id', 'user', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_box_user_user_id', '{{%box_user}}');
        $this->dropIndex('user_id', '{{%box_user}}');
        $this->dropTable('{{%box_user}}');
    }

}
