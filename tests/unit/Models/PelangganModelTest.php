<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MPelanggan;

/**
 * @internal
 */
final class PelangganModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    public function testGenerateKodeReturnsCorrectFormat(): void
    {
        $model = new MPelanggan();
        $kode = $model->generateKode();

        // Should match PLG followed by 4 digits
        $this->assertMatchesRegularExpression('/^PLG\d{4}$/', $kode);
    }

    public function testGenerateKodeIncrementsFromMax(): void
    {
        $model = new MPelanggan();
        $kode = $model->generateKode();

        // With 2 seeded customers, max id is 2, next should be PLG0003
        $this->assertEquals('PLG0003', $kode);
    }

    public function testCanFindSeededCustomer(): void
    {
        $model = new MPelanggan();
        $customer = $model->find(1);

        $this->assertNotNull($customer);
        $this->assertEquals('Budi Santoso', $customer['nama_pelanggan']);
        $this->assertEquals(25, (int) $customer['poin']);
    }

    public function testValidationRejectsInvalidPhone(): void
    {
        $model = new MPelanggan();
        
        $result = $model->insert([
            'kode_pelanggan' => 'PLG9999',
            'nama_pelanggan' => 'Test Invalid',
            'no_telp'        => '12345', // Not starting with 08 or 62, too short
            'alamat'         => 'Alamat Test',
        ]);

        $this->assertFalse($result);
        $errors = $model->errors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('no_telp', $errors);
    }

    public function testValidationRejectsEmptyName(): void
    {
        $model = new MPelanggan();
        
        $result = $model->insert([
            'kode_pelanggan' => 'PLG9998',
            'nama_pelanggan' => '', // empty
            'no_telp'        => '081399990001',
            'alamat'         => 'Alamat Test',
        ]);

        $this->assertFalse($result);
        $errors = $model->errors();
        $this->assertArrayHasKey('nama_pelanggan', $errors);
    }
}
