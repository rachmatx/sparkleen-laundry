<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Feature tests for WhatsApp Log functionality.
 *
 * @internal
 */
final class WhatsAppLogFeatureTest extends FeatureTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    protected $migrateOnce = false;
    protected $migrate = false;
    protected $DBGroup = 'tests';

    protected function setUp(): void
    {
        parent::setUp();

        $filterConfig = config('Filters');
        $filterConfig->methods = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filterConfig);
    }

    public function testLogWhatsappPageLoads(): void
    {
        $this->session([
            'isLoggedIn'    => true,
            'id_karyawan'   => 1,
            'nama_karyawan' => 'Kasir Test',
            'username'      => 'kasir_test',
            'level'         => 'kasir',
        ]);

        $result = $this->call('get', 'log-whatsapp');
        $result->assertOK();
    }

    public function testLogWhatsappRequiresAuth(): void
    {
        $result = $this->call('get', 'log-whatsapp');
        $result->assertRedirectTo(site_url('login'));
    }

    public function testSeededLogsExistInDatabase(): void
    {
        $this->seeInDatabase('tb_log_whatsapp', [
            'no_nota' => 'LAU/20260522/0001',
            'status'  => 'sent',
        ]);

        $this->seeInDatabase('tb_log_whatsapp', [
            'no_nota' => 'LAU/20260522/0001',
            'status'  => 'failed',
        ]);
    }
}
