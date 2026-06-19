<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature tests for WhatsApp Log functionality.
 *
 * @internal
 */
final class WhatsAppLogFeatureTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        $filterConfig = config('Filters');
        $filterConfig->methods = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filterConfig);
    }

    public function testLogWhatsappPageLoads(): void
    {
        $this->withSession([
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
            'no_nota'            => 'LAU/20260522/0001',
            'id_status_whatsapp' => 1,
        ]);

        $this->seeInDatabase('tb_log_whatsapp', [
            'no_nota'            => 'LAU/20260522/0001',
            'id_status_whatsapp' => 2,
        ]);
    }
}
