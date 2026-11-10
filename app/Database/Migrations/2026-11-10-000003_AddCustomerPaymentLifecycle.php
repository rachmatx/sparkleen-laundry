<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddCustomerPaymentLifecycle extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('tb_payment_intent', ['gateway_ref' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true]]);
        $this->forge->addColumn('tb_payment_intent', [
            'request_key' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'active_order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'expires_at' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'payment_detail' => ['type' => 'TEXT', 'null' => true],
            'mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);
        $this->db->query('ALTER TABLE tb_payment_intent ADD UNIQUE KEY payment_request_key (request_key), ADD UNIQUE KEY payment_active_order (active_order_id)');
        $this->db->query("UPDATE tb_payment_intent i JOIN tb_transaksi t ON t.id_transaksi = i.id_transaksi AND t.payment_ref = i.gateway_ref SET i.active_order_id = i.id_transaksi WHERE i.status = 'PENDING'");
        $this->db->resetDataCache();
    }
    public function down()
    {
        // Preserve pending creation attempts; they may already exist at the gateway.
        if ($this->db->table('tb_payment_intent')->where('gateway_ref', null)->countAllResults()) throw new \RuntimeException('Reconcile creation attempts before rollback.');
        $this->db->query('ALTER TABLE tb_payment_intent DROP INDEX payment_request_key, DROP INDEX payment_active_order');
        $this->forge->dropColumn('tb_payment_intent', ['request_key', 'active_order_id', 'expires_at', 'payment_detail', 'mode']);
        $this->forge->modifyColumn('tb_payment_intent', ['gateway_ref' => ['type' => 'VARCHAR', 'constraint' => 100]]);
        $this->db->resetDataCache();
    }
}
