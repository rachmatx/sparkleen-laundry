<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddCustomerNotificationsAndLoyalty extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'id_pelanggan'=>['type'=>'INT','unsigned'=>true],
            'event_key'=>['type'=>'VARCHAR','constraint'=>100],
            'category'=>['type'=>'VARCHAR','constraint'=>20],
            'title'=>['type'=>'VARCHAR','constraint'=>120],
            'body'=>['type'=>'VARCHAR','constraint'=>500],
            'target_type'=>['type'=>'VARCHAR','constraint'=>20],
            'target_id'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'read_at'=>['type'=>'DATETIME','null'=>true],
            'created_at'=>['type'=>'DATETIME'],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addUniqueKey('event_key');
        $this->forge->addKey(['id_pelanggan','read_at','id']);
        $this->forge->addForeignKey('id_pelanggan','tb_pelanggan','id_pelanggan','RESTRICT','RESTRICT');
        $this->forge->createTable('tb_customer_notification');
        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'id_pelanggan'=>['type'=>'INT','unsigned'=>true],
            'id_transaksi'=>['type'=>'INT','unsigned'=>true],
            'request_key'=>['type'=>'VARCHAR','constraint'=>64],
            'points'=>['type'=>'INT','unsigned'=>true],
            'earned_adjustment'=>['type'=>'INT','unsigned'=>true],
            'discount'=>['type'=>'BIGINT','unsigned'=>true],
            'balance_after'=>['type'=>'INT','unsigned'=>true],
            'created_at'=>['type'=>'DATETIME'],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addUniqueKey('id_transaksi');
        $this->forge->addUniqueKey(['id_pelanggan','request_key']);
        $this->forge->addForeignKey('id_pelanggan','tb_pelanggan','id_pelanggan','RESTRICT','RESTRICT');
        $this->forge->addForeignKey('id_transaksi','tb_transaksi','id_transaksi','RESTRICT','RESTRICT');
        $this->forge->createTable('tb_point_redemption');
        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'id_pelanggan'=>['type'=>'INT','unsigned'=>true],
            'id_transaksi'=>['type'=>'INT','unsigned'=>true],
            'event_key'=>['type'=>'VARCHAR','constraint'=>100],
            'delta'=>['type'=>'INT'],
            'balance_after'=>['type'=>'INT','unsigned'=>true],
            'reason'=>['type'=>'VARCHAR','constraint'=>120],
            'created_at'=>['type'=>'DATETIME'],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addUniqueKey('event_key');
        $this->forge->addKey(['id_pelanggan','id']);
        $this->forge->addForeignKey('id_pelanggan','tb_pelanggan','id_pelanggan','RESTRICT','RESTRICT');
        $this->forge->addForeignKey('id_transaksi','tb_transaksi','id_transaksi','RESTRICT','RESTRICT');
        $this->forge->createTable('tb_point_movement');
        $this->db->resetDataCache();
    }
    public function down()
    {
        // Discounted orders and balances cannot be reconstructed by dropping audit data.
        $fixtureReset = ENVIRONMENT === 'testing' && $this->db->getDatabase() === 'laundry_db_test'
            && in_array($this->db->hostname, ['127.0.0.1','localhost'], true);
        if (!$fixtureReset && ($this->db->table('tb_point_movement')->countAllResults() || $this->db->table('tb_point_redemption')->countAllResults())) {
            throw new \RuntimeException('Keep loyalty audit data; restore a consistent backup for rollback.');
        }
        foreach (['tb_point_movement','tb_point_redemption','tb_customer_notification'] as $table) $this->forge->dropTable($table,true);
        $this->db->resetDataCache();
    }
}
