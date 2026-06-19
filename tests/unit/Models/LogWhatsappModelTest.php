<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MLogWhatsapp;

/**
 * @internal
 */
final class LogWhatsappModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    public function testGetDaftarLogWithStatusFilter(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog('sukses');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $this->assertEquals('sukses', $log->status ?? $log['status']);
        }
    }

    public function testGetDaftarLogWithFailedStatus(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog('gagal');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $this->assertEquals('gagal', $log->status ?? $log['status']);
        }
    }

    public function testGetDaftarLogWithSearchFilter(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog(null, null, null, null, 'LAU/20260522');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }

    public function testGetDaftarLogWithTipeFilter(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog(null, 'order_baru');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }

    public function testGetDaftarLogWithNoFiltersReturnsAll(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog();

        $this->assertIsArray($logs);
        $this->assertCount(2, $logs); // 2 seeded logs
    }

    public function testGetDaftarLogRespectsLimit(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog(null, null, null, null, null, 1, 0);

        $this->assertIsArray($logs);
        $this->assertCount(1, $logs);
    }

    public function testGetDaftarLogWithDateRange(): void
    {
        $model = new MLogWhatsapp();
        $today = date('Y-m-d');
        $logs = $model->getDaftarLog(null, null, $today, $today);

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }
}
