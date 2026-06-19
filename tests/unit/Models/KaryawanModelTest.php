<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MKaryawan;

/**
 * @internal
 */
final class KaryawanModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    public function testGetUserByIdentifierWithUsername(): void
    {
        $model = new MKaryawan();
        $user = $model->getUserByIdentifier('kasir_test');

        $this->assertNotNull($user);
        $this->assertEquals('kasir_test', $user->username);
        $this->assertEquals('kasir', $user->level);
    }

    public function testGetUserByIdentifierWithEmail(): void
    {
        $model = new MKaryawan();
        $user = $model->getUserByIdentifier('admin@test.com');

        $this->assertNotNull($user);
        $this->assertEquals('admin_test', $user->username);
        $this->assertEquals('admin', $user->level);
    }

    public function testGetUserByIdentifierReturnsNullForUnknown(): void
    {
        $model = new MKaryawan();
        $user = $model->getUserByIdentifier('nonexistent_user');

        $this->assertNull($user);
    }

    public function testGetUserByIdentifierWithEmailDetectsAtSign(): void
    {
        $model = new MKaryawan();
        // Should search by email field when @ is present
        $user = $model->getUserByIdentifier('kasir@test.com');

        $this->assertNotNull($user);
        $this->assertEquals('kasir_test', $user->username);
    }

    public function testDeactivatedUserCanBeFound(): void
    {
        $model = new MKaryawan();
        $user = $model->getUserByIdentifier('nonaktif_test');

        $this->assertNotNull($user);
        $this->assertEquals(0, (int) $user->is_aktif);
    }
}
