<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Models\Bnnk;
use App\Models\NarcoticType;
use App\Models\TatRegister;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

class TatRegisterSeeder extends Seeder
{
    /**
     * Jumlah data demo per BNNK untuk tiap kategori.
     * Total per BNNK = 5 final + 5 draft + 5 arsip = 15 baris.
     */
    private const PER_BNNK_FINAL = 5;

    private const PER_BNNK_DRAFT = 5;

    private const PER_BNNK_ARCHIVED = 5;

    public function run(): void
    {
        $bnnks = Bnnk::query()->orderBy('name')->get();

        $types = NarcoticType::query()->where('is_active', true)->orderBy('id')->get()->values();
        $units = Unit::query()->where('is_active', true)->orderBy('id')->get()->values();

        if ($bnnks->isEmpty() || $types->isEmpty() || $units->isEmpty()) {
            throw new RuntimeException('TatRegisterSeeder requires seeded BNNKs, active narcotic types, and active units.');
        }

        $this->removeExistingDevelopmentRecords();

        $sequence = 0;

        foreach ($bnnks as $bnnk) {
            $this->seedFinalRecords($bnnk, $sequence++, $types, $units);
            $this->seedDraftRecords($bnnk, $sequence++);
            $this->seedArchivedRecords($bnnk, $sequence++, $types, $units);
        }
    }

    private function removeExistingDevelopmentRecords(): void
    {
        TatRegister::withTrashed()
            ->where('nama', 'like', 'Nama Demo %')
            ->get()
            ->each(fn (TatRegister $record) => $record->forceDelete());
    }

    private function shortCode(Bnnk $bnnk, int $sequence): string
    {
        // Kode unik per BNNK untuk NO REGISTER demo,
        // memakai id agar stabil antar-seed dan tidak tabrakan
        // walau ada dua BNNK dengan awalan huruf yang sama.
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $bnnk->name));
        $code = substr($letters, 0, 3);
        $code = str_pad($code, 3, 'X');

        return sprintf('%s%02d%02d', $code, $bnnk->id % 100, $sequence % 100);
    }

    private function seedFinalRecords(Bnnk $bnnk, int $sequence, Collection $types, Collection $units): void
    {
        $code = $this->shortCode($bnnk, $sequence);
        $shortName = $this->displayShortName($bnnk);
        $recommendations = [TatRecommendation::RawatJalan, TatRecommendation::RawatInap, TatRecommendation::RehabLapasRutan, TatRecommendation::TidakRehab];
        $genders = [Gender::L, Gender::P];
        $typeCount = $types->count();
        $unitCount = $units->count();

        for ($i = 1; $i <= self::PER_BNNK_FINAL; $i++) {
            $no = ($sequence * 100) + $i;
            $recommendation = $recommendations[($sequence + $i) % count($recommendations)];
            $execution = $recommendation === TatRecommendation::TidakRehab
                ? RecommendationExecution::TidakDilaksanakan
                : RecommendationExecution::Dilaksanakan;

            // Variasikan pola narkotika: tanpa BB, satu item, dan dua item.
            $narcotics = match ($i % 3) {
                0 => [],
                1 => [[$types[($sequence + $i) % $typeCount]->id, number_format(0.5 * $i, 3, '.', ''), $units[($sequence + $i) % $unitCount]->id]],
                default => [
                    [$types[($sequence + $i) % $typeCount]->id, number_format(1.25 * $i, 3, '.', ''), $units[($sequence + $i) % $unitCount]->id],
                    [$types[($sequence + $i + 1) % $typeCount]->id, number_format(2.0 * $i, 3, '.', ''), $units[($sequence + $i + 1) % $unitCount]->id],
                ],
            };

            $this->createRecord($bnnk, [
                'no' => $no,
                'no_bln' => $i,
                'no_register' => sprintf('DEV/TAT/%s/%03d/2026', $code, $i),
                'nama' => sprintf('Nama Demo %s Final %02d', $shortName, $i),
                'tanggal_lahir' => sprintf('199%d-%02d-%02d', $i % 10, ($i % 12) + 1, ($i % 27) + 1),
                'jenis_kelamin' => $genders[($sequence + $i) % 2],
                'usia' => 28 + (($sequence + $i) % 12),
                'rekomendasi_tat' => $recommendation,
                'pelaksanaan_rekomendasi' => $execution,
            ], $narcotics, true);
        }
    }

    private function seedDraftRecords(Bnnk $bnnk, int $sequence): void
    {
        $shortName = $this->displayShortName($bnnk);

        $variants = [
            ['keterangan' => 'Draft demo tanpa NIK dan NO REGISTER.'],
            ['nik' => sprintf('DUMMY-NIK-%02d-D1', $sequence), 'keterangan' => 'Draft demo tanpa rekomendasi.'],
            ['keterangan' => 'Draft demo tanpa hasil asesmen.'],
            ['tanggal_lahir' => '1997-04-12', 'jenis_kelamin' => Gender::P, 'keterangan' => 'Draft demo dengan identitas parsial.'],
            ['nik' => null, 'foto_klien' => null, 'keterangan' => 'Draft demo untuk alur pelengkapan data.'],
        ];

        for ($i = 1; $i <= self::PER_BNNK_DRAFT; $i++) {
            $this->createRecord($bnnk, array_merge(
                ['nama' => sprintf('Nama Demo %s Draft %02d', $shortName, $i)],
                $variants[($i - 1) % count($variants)]
            ));
        }
    }

    private function seedArchivedRecords(Bnnk $bnnk, int $sequence, Collection $types, Collection $units): void
    {
        $code = $this->shortCode($bnnk, $sequence);
        $shortName = $this->displayShortName($bnnk);
        $recommendations = [TatRecommendation::RawatInap, TatRecommendation::RawatJalan, TatRecommendation::RehabLapasRutan, TatRecommendation::TidakRehab];
        $typeCount = $types->count();
        $unitCount = $units->count();

        // Nomor yang sudah dipakai record non-demo (mis. data manual "Adit")
        // harus dilewati agar tidak tabrakan scope unik (bnnk, bulan, tahun, no_bln).
        $occupied = TatRegister::withTrashed()
            ->where('bnnk_id', $bnnk->id)
            ->where('no_bln_month', 9)
            ->where('no_bln_year', 2026)
            ->pluck('no_bln')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->all();

        for ($i = 1; $i <= self::PER_BNNK_ARCHIVED; $i++) {
            // NO/BLN arsip dilanjutkan setelah nomor final, loncat bila sudah terisi.
            $noBln = self::PER_BNNK_FINAL + $i;
            while (in_array($noBln, $occupied, true)) {
                $noBln++;
            }
            $occupied[] = $noBln;
            $no = ($sequence * 100) + 50 + $i;
            $recommendation = $recommendations[($sequence + $i) % count($recommendations)];

            $this->createRecord($bnnk, [
                'no' => $no,
                'no_bln' => $noBln,
                'no_register' => sprintf('DEV/TAT/%s/A%02d/2026', $code, $i),
                'nama' => sprintf('Nama Demo %s Arsip %02d', $shortName, $i),
                'tanggal_lahir' => sprintf('199%d-%02d-%02d', ($i + 3) % 10, (($i + 4) % 12) + 1, (($i + 5) % 27) + 1),
                'jenis_kelamin' => $i % 2 === 0 ? Gender::P : Gender::L,
                'usia' => 30 + (($sequence + $i) % 10),
                'rekomendasi_tat' => $recommendation,
                'pelaksanaan_rekomendasi' => $recommendation === TatRecommendation::TidakRehab
                    ? RecommendationExecution::TidakDilaksanakan
                    : RecommendationExecution::Dilaksanakan,
            ], $i % 2 === 0 ? [] : [[$types[($sequence + $i) % $typeCount]->id, number_format(1.5 * $i, 3, '.', ''), $units[($sequence + $i) % $unitCount]->id]], true, true);
        }
    }

    private function displayShortName(Bnnk $bnnk): string
    {
        $short = preg_replace('/^BNN\s+(Kota|Kabupaten)\s+/i', '', $bnnk->name);

        return $short !== '' ? $short : $bnnk->name;
    }

    private function createRecord(Bnnk $bnnk, array $attributes, array $narcotics = [], bool $final = false, bool $archive = false): TatRegister
    {
        $attributes = array_merge($this->commonAttributes($attributes), $attributes);

        $factory = TatRegister::factory()->for($bnnk);
        $record = ($final ? $factory->final() : $factory->draft())->create($attributes);

        foreach ($narcotics as [$typeId, $quantity, $unitId]) {
            $record->narcotics()->create([
                'narcotic_type_id' => $typeId,
                'jumlah' => $quantity,
                'unit_id' => $unitId,
            ]);
        }

        if ($archive) {
            $record->delete();
        }

        return $record;
    }

    private function commonAttributes(array $attributes): array
    {
        $no = $attributes['no'] ?? null;
        $noBln = $attributes['no_bln'] ?? null;

        return [
            'no' => $no,
            'no_bln' => $noBln,
            'no_bln_month' => $noBln === null ? null : 9,
            'no_bln_year' => $noBln === null ? null : 2026,
            'asal_pengajuan' => 'Unit Demo Pengajuan',
            'tanggal_surat' => $no === null ? null : '2026-09-01',
            'tanggal_berkas_diterima' => $no === null ? null : '2026-09-02',
            'tanggal_pelaksanaan' => $no === null ? null : '2026-09-03',
            'no_surat_pengajuan' => $no === null ? null : 'DEV/SURAT/'.str_pad((string) $no, 3, '0', STR_PAD_LEFT),
            'no_lkn_lp_li' => $no === null ? null : 'DEV/LKN/'.str_pad((string) $no, 3, '0', STR_PAD_LEFT),
            'pasal_yang_disangkakan' => $no === null ? null : 'Pasal 111 ayat (1) UU Nomor 35 Tahun 2009',
            'tanggal_penangkapan' => $no === null ? null : '2026-08-30',
            'alamat_ktp' => $no === null ? null : 'Alamat KTP Demo '.$no,
            'alamat_domisili' => $no === null ? null : 'Alamat Domisili Demo '.$no,
            'tempat_lahir' => $no === null ? null : 'Kota Demo',
            'pendidikan' => $no === null ? null : 'SMA Demo',
            'pekerjaan' => $no === null ? null : 'Pekerjaan Demo',
            'no_handphone' => $no === null ? null : '000-DEV-'.str_pad((string) $no, 3, '0', STR_PAD_LEFT),
            'hasil_asesmen_hukum' => $no === null ? null : 'Hasil asesmen hukum dummy untuk pengembangan.',
            'hasil_asesmen_medis' => $no === null ? null : 'Hasil asesmen medis dummy untuk pengembangan.',
            'hasil_rekom_tat' => $no === null ? null : 'Lembaga rehabilitasi demo.',
            'keterangan' => 'Data synthetic development seed.',
        ];
    }
}
