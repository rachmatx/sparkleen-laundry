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
    protected $seedOnce = false;
    protected $migrateOnce = false;
    protected $migrate = false;
    protected $DBGroup = 'tests';

    public function testGetDaftarLogWithStatusFilter(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog('sent');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $this->assertEquals('sent', $log->status ?? $log['status']);
        }
    }

    public function testGetDaftarLogWithFailedStatus(): void
    {
        $model = new MLogWhatsapp();
        $logs = $model->getDaftarLog('failed');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $this->assertEquals('failed', $log->status ?? $log['status']);
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
