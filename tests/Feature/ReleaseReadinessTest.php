<?php

namespace Tests\Feature;

use App\Services\BackupService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class ReleaseReadinessTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrateOnce = true;
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;

    public function testDiagnosticAndSeederRoutesAreNotRegisteredOutsideDevelopment(): void
    {
        $routes = service('routes')->getRoutes('GET');
        foreach (['debug', 'debug-db', 'seed'] as $path) {
            $this->assertArrayNotHasKey($path, $routes);
        }
    }

    public function testLegacyBackupCanBeRestoredAndMigratedWithoutChangingBusinessRows(): void
    {
        $config = config('Database')->tests;
        if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test'
            || !in_array($config['hostname'], ['localhost', '127.0.0.1'], true)) {
            throw new \RuntimeException('Restore rehearsal requires the disposable local test database.');
        }

        $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->update([
            'alamat' => "Jl. 'Cemara' \\ Blok A\nDepok 🌿",
        ]);
        $snapshot = $this->businessRows();
        $runner = service('migrations')->setNamespace('App');
        $path = APPPATH . 'Database/Migrations/2026-11-09-000001_AddCustomerMobileAuth.php';

        // Toggle only the new migration down, including its history entry.
        $this->assertTrue($runner->force($path, 'App', 'tests'));
        $this->db->resetDataCache();
        $this->assertFalse($this->db->fieldExists('pin_hash', 'tb_pelanggan'));
        $this->assertSame($snapshot, $this->businessRows());
        $dump = (new BackupService())->generateSqlDump();

        // Restore the actual backup output after changing a row, so an empty
        // or incomplete backup cannot satisfy the round-trip assertion.
        $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->update(['alamat' => 'changed after backup']);
        $connection = $this->db->connID;
        if (!$connection->multi_query($dump)) {
            throw new \RuntimeException('Local backup restore failed.');
        }
        do {
            if ($result = $connection->store_result()) {
                $result->free();
            }
            if (!$connection->more_results()) {
                break;
            }
            if (!$connection->next_result()) {
                throw new \RuntimeException('Local backup restore failed.');
            }
        } while (true);
        $this->db->resetDataCache();
        $this->assertSame($snapshot, $this->businessRows());

        $this->assertTrue($runner->latest('tests'));
        $this->db->resetDataCache();
        $this->assertTrue($this->db->fieldExists('pin_hash', 'tb_pelanggan'));
        $this->assertTrue($this->db->tableExists('tb_api_token'));
        $this->assertTrue($this->db->tableExists('tb_api_auth_attempts'));
        $this->assertSame($snapshot, $this->businessRows());
    }

    private function businessRows(): array
    {
        $rows = [];
        foreach (['tb_pelanggan', 'tb_transaksi', 'tb_detail_transaksi', 'tb_pembayaran', 'tb_inventaris', 'tb_penggunaan_inventaris'] as $table) {
            $rows[$table] = $this->db->table($table)->get()->getResultArray();
            foreach ($rows[$table] as &$row) {
                if ($table === 'tb_pelanggan') {
                    unset($row['pin_hash'], $row['pin_set_at'], $row['last_login_at']);
                }
                ksort($row);
            }
            unset($row);
            usort($rows[$table], static fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
        }
        return $rows;
    }
}
