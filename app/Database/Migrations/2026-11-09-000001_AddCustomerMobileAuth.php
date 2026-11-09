<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerMobileAuth extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_pelanggan', [
            'pin_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pin_set_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField([
            'id_token' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_pelanggan' => ['type' => 'INT', 'unsigned' => true],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id_token', true);
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('id_pelanggan');
        $this->forge->addForeignKey('id_pelanggan', 'tb_pelanggan', 'id_pelanggan', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_api_token');
        $this->forge->addField([
            'bucket_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'attempts' => ['type' => 'INT', 'unsigned' => true],
            'window_start' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey('bucket_hash', true);
        $this->forge->addKey('window_start');
        $this->forge->createTable('tb_api_auth_attempts');
        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->forge->dropTable('tb_api_auth_attempts', true);
        $this->forge->dropTable('tb_api_token', true);
        $this->forge->dropColumn('tb_pelanggan', ['pin_hash', 'pin_set_at', 'last_login_at']);
        $this->db->resetDataCache();
    }
}
