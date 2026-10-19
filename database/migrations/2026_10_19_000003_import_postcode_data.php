<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A migráció automatikusan betölti a kezdeti adatokat. Nem szükséges --seed vagy SQL-import.
 * A forrásfájlok: database/data/*.json, eredeti SQL: database/sql/postoffice.dump.sql.
 */
return new class extends Migration {
    public function up(): void
    {
        $counties = $this->loadRows('counties.json', 20);
        $cities = $this->loadRows('cities.json', 3574);

        DB::transaction(function () use ($counties, $cities): void {
            DB::table('counties')->insert($counties);
            foreach (array_chunk($cities, 200) as $batch) {
                DB::table('cities')->insert($batch);
            }
        });
    }

    public function down(): void
    {
        // Teljes visszagörgetéskor előbb az importált rekordok, majd a táblák törlődnek.
        // Az esetleges később felvett rekordok miatt a tábla-bontó migrációval együtt
        // használd, és fejlesztési környezetben készíts előtte mentést.
        DB::table('cities')->whereBetween('id', [1, 3574])->delete();
        DB::table('counties')->whereBetween('id', [1, 20])->delete();
    }

    private function loadRows(string $filename, int $expected): array
    {
        $path = database_path('data/'.$filename);
        if (!is_readable($path)) {
            throw new RuntimeException('Hiányzó adatfájl: '.$path);
        }

        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows) || count($rows) !== $expected) {
            throw new RuntimeException("Hibás adatfájl ({$filename}), elvárt rekordszám: {$expected}.");
        }
        return $rows;
    }
};
