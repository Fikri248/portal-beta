<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Bnnk;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 18 BNN Kabupaten/Kota di bawah BNNP Jawa Timur.
        // Email mengikuti format bnnk_namakota@bnn.go.id
        // (kota/kab dimasukkan ke local-part agar Kota vs Kabupaten tidak tabrakan,
        //  mis. Kota Malang vs Kabupaten Malang).
        $bnnkAccounts = [
            ['name' => 'BNN Kota Malang', 'email' => 'bnnk_kota_malang@bnn.go.id'],
            ['name' => 'BNN Kota Kediri', 'email' => 'bnnk_kota_kediri@bnn.go.id'],
            ['name' => 'BNN Kota Batu', 'email' => 'bnnk_kota_batu@bnn.go.id'],
            ['name' => 'BNN Kota Surabaya', 'email' => 'bnnk_kota_surabaya@bnn.go.id'],
            ['name' => 'BNN Kota Mojokerto', 'email' => 'bnnk_kota_mojokerto@bnn.go.id'],
            ['name' => 'BNN Kabupaten Nganjuk', 'email' => 'bnnk_kab_nganjuk@bnn.go.id'],
            ['name' => 'BNN Kabupaten Blitar', 'email' => 'bnnk_kab_blitar@bnn.go.id'],
            ['name' => 'BNN Kabupaten Sidoarjo', 'email' => 'bnnk_kab_sidoarjo@bnn.go.id'],
            ['name' => 'BNN Kabupaten Tulungagung', 'email' => 'bnnk_kab_tulungagung@bnn.go.id'],
            ['name' => 'BNN Kabupaten Kediri', 'email' => 'bnnk_kab_kediri@bnn.go.id'],
            ['name' => 'BNN Kabupaten Malang', 'email' => 'bnnk_kab_malang@bnn.go.id'],
            ['name' => 'BNN Kabupaten Lumajang', 'email' => 'bnnk_kab_lumajang@bnn.go.id'],
            ['name' => 'BNN Kabupaten Gresik', 'email' => 'bnnk_kab_gresik@bnn.go.id'],
            ['name' => 'BNN Kabupaten Trenggalek', 'email' => 'bnnk_kab_trenggalek@bnn.go.id'],
            ['name' => 'BNN Kabupaten Sumenep', 'email' => 'bnnk_kab_sumenep@bnn.go.id'],
            ['name' => 'BNN Kabupaten Pasuruan', 'email' => 'bnnk_kab_pasuruan@bnn.go.id'],
            ['name' => 'BNN Kabupaten Tuban', 'email' => 'bnnk_kab_tuban@bnn.go.id'],
            ['name' => 'BNN Kabupaten Banyuwangi', 'email' => 'bnnk_kab_banyuwangi@bnn.go.id'],
        ];

        User::updateOrCreate(['email' => 'bnnp_jatim@bnn.go.id'], [
            'name' => 'BNNP Jawa Timur',
            'password' => 'password',
            'role' => UserRole::Bnnp,
            'bnnk_id' => null,
            'is_active' => true,
        ]);

        foreach ($bnnkAccounts as $account) {
            $bnnk = Bnnk::firstOrCreate(['name' => $account['name']]);

            User::updateOrCreate(['email' => $account['email']], [
                'name' => $account['name'],
                'password' => 'password',
                'role' => UserRole::Bnnk,
                'bnnk_id' => $bnnk->id,
                'is_active' => true,
            ]);
        }

        // Bersihkan format email lama yang sudah digantikan format baru.
        User::query()->whereIn('email', [
            'bnnkota_surabaya@bnn.go.id',
            'bnnkota_malang@bnn.go.id',
        ])->delete();

        $this->call([NarcoticTypeSeeder::class, UnitSeeder::class]);
        $this->call(TatRegisterSeeder::class);
    }
}


