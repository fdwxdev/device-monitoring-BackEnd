<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SitesDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Client ──────────────────────────────────────────────────────────
        $clientId = DB::table('clients')->insertGetId([
            'name'  => 'Atlas Farm',
            'email' => 'atlas@farm.ma',
            'phone' => '+212 6 00 11 22 33',
        ]);

        // ── 2. Super-Admin user (skip if already exists) ───────────────────────
        if (!User::where('email', 'admin@1234.com')->exists()) {
            User::create([
                'name'      => 'Super Admin',
                'email'     => 'admin@1234.com',
                'password'  => Hash::make('1234'),
                'role'      => 'superadmin',
            ]);
        }

        // ── 3. Sites ───────────────────────────────────────────────────────────
        $sites = [
            ['name' => 'Ferme Marrakech',    'adress' => "Route d'Ourika, Km 12", 'city' => 'Marrakech, Maroc',    'status' => 'Actif'],
            ['name' => 'Station Irrigation', 'adress' => 'Douar Lagrhibate',       'city' => 'Beni Mellal, Maroc', 'status' => 'Actif'],
            ['name' => 'Serre Agadir',       'adress' => "Route d'Agadir, Km 18", 'city' => 'Agadir, Maroc',      'status' => 'Actif'],
            ['name' => 'Ferme Midelt',       'adress' => 'Douar Ait Ouchaou',      'city' => 'Midelt, Maroc',      'status' => 'Actif'],
            ['name' => 'Ferme Taounate',     'adress' => 'Douar Tazrout',          'city' => 'Taounate, Maroc',    'status' => 'Actif'],
            ['name' => 'Bassin Stockage',    'adress' => 'Douar Aloudiane',        'city' => 'El Jadida, Maroc',   'status' => 'Alerte'],
            ['name' => 'Ferme Beni Mellal',  'adress' => 'Route Nationale 8',      'city' => 'Beni Mellal, Maroc', 'status' => 'Actif'],
        ];

        $siteIds = [];
        foreach ($sites as $site) {
            $siteIds[] = DB::table('sites')->insertGetId([
                'name'      => $site['name'],
                'adress'    => $site['adress'],
                'city'      => $site['city'],
                'status'    => $site['status'],
                'id_client' => $clientId,
            ]);
        }

        // ── 4. Devices (2-3 per site) ─────────────────────────────────────────
        $deviceNames = [
            'Contrôleur Principal', 'Module IoT', 'Gateway LoRa',
            'Nœud Capteur A',       'Nœud Capteur B', 'RTU Modbus',
        ];

        $deviceIds = [];
        foreach ($siteIds as $idx => $siteId) {
            $count = ($idx % 2 === 0) ? 2 : 3;
            for ($i = 0; $i < $count; $i++) {
                $deviceIds[$siteId][] = DB::table('devices')->insertGetId([
                    'name'    => $deviceNames[($idx + $i) % count($deviceNames)],
                    'status'  => ($idx === 5 && $i === 0) ? 'Offline' : 'Online',
                    'id_site' => $siteId,
                ]);
            }
        }

        // ── 5. Equipements (2-4 per device) ──────────────────────────────────
        $equipNames = [
            'Pompe Irrigation', 'Vanne Électrique', 'Station Météo',
            'Débitmètre',       'Sonde Température', 'Relais Commande',
        ];

        $equipIds = [];
        foreach ($deviceIds as $siteId => $devs) {
            foreach ($devs as $dIdx => $deviceId) {
                $count = 2 + ($dIdx % 2);
                for ($i = 0; $i < $count; $i++) {
                    $equipIds[$deviceId][] = DB::table('equipement')->insertGetId([
                        'name'      => $equipNames[($dIdx + $i) % count($equipNames)],
                        'type'      => 'Automatisé',
                        'id_device' => $deviceId,
                    ]);
                }
            }
        }

        // ── 6. Capteurs (2-4 per equipment) ──────────────────────────────────
        $sensorDefs = [
            ['name' => 'Température Sol',       'type' => 'Température',  'unit' => '°C'],
            ['name' => 'Humidité Air',           'type' => 'Humidité',     'unit' => '%'],
            ['name' => 'Débit Eau',              'type' => 'Débit',        'unit' => 'L/h'],
            ['name' => 'Pression',               'type' => 'Pression',     'unit' => 'bar'],
            ['name' => 'Niveau Réservoir',       'type' => 'Niveau',       'unit' => 'cm'],
            ['name' => 'Conductivité',           'type' => 'Conductivité', 'unit' => 'µS/cm'],
            ['name' => 'pH Eau',                 'type' => 'pH',           'unit' => 'pH'],
            ['name' => 'Luminosité',             'type' => 'Lumière',      'unit' => 'Lux'],
        ];

        foreach ($equipIds as $deviceId => $equips) {
            foreach ($equips as $eIdx => $equipId) {
                $count = 2 + ($eIdx % 3);
                for ($i = 0; $i < $count; $i++) {
                    $s = $sensorDefs[($eIdx + $i) % count($sensorDefs)];
                    DB::table('sensors')->insert([
                        'name'         => $s['name'],
                        'type'         => $s['type'],
                        'unit'         => $s['unit'],
                        'value'        => round(rand(10, 100) + rand(0, 99) / 100, 2),
                        'id_equipment' => $equipId,
                    ]);
                }
            }
        }
    }
}
