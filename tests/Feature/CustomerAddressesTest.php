<?php

namespace Tests\Feature;

use App\Libraries\Mobile\CustomerAuth;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
final class CustomerAddressesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrateOnce = true;
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    private string $token, $other;
    protected function setUp(): void
    {
        parent::setUp();
        $auth = new CustomerAuth();
        $this->token = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray())['token'];
        $this->other = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan', 2)->get()->getRowArray())['token'];
    }
    private function api(string $method, string $path, array $body = [], ?string $token = null)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . ($token ?? $this->token), 'Accept' => 'application/json'])->withBodyFormat('json')->call($method, 'api/v1/' . $path, $body);
    }
    private function data($r): array
    {
        return json_decode($r->getJSON(), true)['data'];
    }
    private function create(array $patch = []): array
    {
        $r = $this->api('POST', 'alamat', array_replace(['label' => 'Kos', 'alamat' => 'Jl. Melati Nomor 12, Depok', 'patokan' => 'Pagar biru'], $patch));
        $r->assertStatus(201);
        return $this->data($r);
    }
    public function testLegacyAddressAndNewRegistrationAreImportedOnce(): void
    {
        $one = $this->data($this->api('GET', 'alamat'));
        $two = $this->data($this->api('GET', 'alamat'));
        $this->assertCount(1, $one);
        $this->assertSame($one, $two);
        $this->assertTrue($one[0]['is_default']);
        $this->assertSame($this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray()['alamat'], $one[0]['alamat']);
        $new = $this->call('POST', 'api/v1/auth/register', ['nama' => 'Alamat Baru', 'no_telp' => '081234555678', 'alamat' => 'Jl. Kenanga Nomor 55', 'pin' => '123456']);
        $new->assertStatus(201);
        $token = $this->data($new)['token'];
        $r = $this->api('GET', 'alamat', [], $token);
        $r->assertStatus(200);
        $this->assertCount(1, $this->data($r));
    }
    public function testCreateReadUpdateDeleteAndDefaultMirror(): void
    {
        $a = $this->create(['is_default' => true]);
        $this->assertTrue($a['is_default']);
        $this->api('GET', 'alamat/' . $a['id_alamat'])->assertStatus(200);
        $r = $this->api('PATCH', 'alamat/' . $a['id_alamat'], ['label' => 'Kantor', 'alamat' => 'Jl. Kantor Baru Nomor 10']);
        $r->assertStatus(200);
        $this->assertSame('Kantor', $this->data($r)['label']);
        $this->assertSame('Jl. Kantor Baru Nomor 10', $this->data($this->api('GET', 'me'))['alamat']);
        $this->api('DELETE', 'alamat/' . $a['id_alamat'])->assertStatus(204);
        $this->api('GET', 'alamat/' . $a['id_alamat'])->assertStatus(404);
        $rows = $this->data($this->api('GET', 'alamat'));
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['is_default']);
    }
    public function testOwnershipIsCheckedForEveryOperation(): void
    {
        $a = $this->create();
        $path = 'alamat/' . $a['id_alamat'];
        $this->api('GET', $path, [], $this->other)->assertStatus(404);
        $this->api('PATCH', $path, ['label' => 'Steal'], $this->other)->assertStatus(404);
        $this->api('POST', $path . '/utama', [], $this->other)->assertStatus(404);
        $this->api('DELETE', $path, [], $this->other)->assertStatus(404);
        $r = $this->api('GET', 'alamat', [], $this->other);
        foreach ($this->data($r) as $row) {
            $this->assertNotSame($a['id_alamat'], $row['id_alamat']);
        }
    }
    public function testDefaultSwitchAndDeleteLastDoNotResurrectAddress(): void
    {
        $a = $this->create();
        $b = $this->create(['label' => 'Rumah']);
        foreach ([$a, $b, $a] as $pick) {
            $this->api('POST', 'alamat/' . $pick['id_alamat'] . '/utama')->assertStatus(200);
            $this->assertSame(1, $this->db->table('tb_alamat_pelanggan')->where('utama_pelanggan_id', 1)->countAllResults());
        }
        foreach ($this->data($this->api('GET', 'alamat')) as $row) {
            $this->api('DELETE', 'alamat/' . $row['id_alamat'])->assertStatus(204);
        }
        $this->assertSame([], $this->data($this->api('GET', 'alamat')));
        $this->assertSame('', $this->data($this->api('GET', 'me'))['alamat']);
        $this->assertTrue($this->create()['is_default']);
    }
    public function testValidationPaginationAndLegacyProfileSync(): void
    {
        foreach ([['label' => ''], ['alamat' => 'short'], ['patokan' => str_repeat('a', 256)], ['is_default' => 'yes']] as $patch) {
            $this->api('POST', 'alamat', array_replace(['label' => 'Rumah', 'alamat' => 'Jl. Merdeka Nomor 10'], $patch))->assertStatus(422);
        }
        for ($i = 0; $i < 21; $i++) {
            $this->create(['label' => 'Alamat ' . $i]);
        }
        $one = $this->api('GET', 'alamat');
        $this->assertCount(20, $this->data($one));
        $this->assertCount(2, $this->data($this->api('GET', 'alamat?page=2')));
        $this->api('GET', 'alamat?per_page=100')->assertStatus(422);
        $this->api('PATCH', 'me', ['alamat' => 'Jl. Profil Baru Nomor 20'])->assertStatus(200);
        $this->assertSame('Jl. Profil Baru Nomor 20', $this->data($this->api('GET', 'alamat'))[0]['alamat']);
    }
    public function testPickupUsesOwnedSnapshotAndSurvivesAddressDeletion(): void
    {
        $a = $this->create();
        $date = (new \DateTimeImmutable('tomorrow', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $body = ['id_alamat' => $a['id_alamat'], 'id_layanan' => 1, 'berat_perkiraan' => 2, 'tgl_jemput' => $date, 'slot_jemput' => 'siang', 'alamat_jemput' => 'Ignored forged street'];
        $this->api('POST', 'permintaan', $body, $this->other)->assertStatus(404);
        $r = $this->api('POST', 'permintaan', $body);
        $r->assertStatus(201);
        $pickup = $this->data($r);
        $this->assertSame($a['alamat'], $pickup['alamat_jemput']);
        $this->assertSame('Pagar biru', $pickup['patokan_jemput']);
        $this->api('PATCH', 'alamat/' . $a['id_alamat'], ['alamat' => 'Jl. Baru Nomor 900'])->assertStatus(200);
        $this->api('DELETE', 'alamat/' . $a['id_alamat'])->assertStatus(204);
        $stored = $this->data($this->api('GET', 'permintaan/' . $pickup['id_permintaan']));
        $this->assertSame($a['alamat'], $stored['alamat_jemput']);
        $this->assertSame('Pagar biru', $stored['patokan_jemput']);
    }
    public function testDatabaseFailureRollsBackNewDefaultAndLegacyMirror(): void
    {
        $old = $this->data($this->api('GET', 'alamat'));
        $this->db->query("CREATE TRIGGER fail_address_mirror BEFORE UPDATE ON tb_pelanggan FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
        try {
            $this->api('POST', 'alamat', ['label' => 'Failure', 'alamat' => 'Jl. Tidak Boleh Tersimpan', 'is_default' => true])->assertStatus(500);
        } finally {
            $this->db->query('DROP TRIGGER fail_address_mirror');
        }
        $this->assertSame($old, $this->data($this->api('GET', 'alamat')));
        $this->assertSame($old[0]['alamat'], $this->data($this->api('GET', 'me'))['alamat']);
    }
}
