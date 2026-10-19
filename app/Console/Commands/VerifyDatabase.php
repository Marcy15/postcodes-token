<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDatabase extends Command
{
    protected $signature = 'postcodes:verify';
    protected $description = 'Ellenőrzi, hogy a migráció minden alapadatot betöltött-e.';

    public function handle(): int
    {
        $cities = DB::table('cities')->count();
        $counties = DB::table('counties')->count();
        $missing = DB::table('cities')->leftJoin('counties', 'cities.id_county', '=', 'counties.id')
            ->whereNull('counties.id')->count();
        $vac = DB::table('cities')->where('city', 'Vác')->where('zip_code', 2600)->exists();

        $this->line("Településrekordok: {$cities} (eredetileg 3574)");
        $this->line("Megyék: {$counties} (eredetileg 20)");
        $this->line("Hiányzó megyekapcsolatok: {$missing}");
        $this->line('Vác, 2600: '.($vac ? 'megtalálható' : 'HIÁNYZIK'));

        if ($cities < 3574 || $counties < 20 || $missing !== 0 || !$vac) {
            $this->error('A betöltött adatok ellenőrzése hibát talált.');
            return self::FAILURE;
        }

        $this->info('Az adatbázis alapadatai megfelelően betöltődtek.');
        return self::SUCCESS;
    }
}
