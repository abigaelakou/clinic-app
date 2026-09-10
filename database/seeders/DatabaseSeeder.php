<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Specialty;
use App\Models\StockCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder de démonstration pour la clinique FAME.
 * Idempotent : peut être relancé plusieurs fois sans créer de doublons
 * (utilise updateOrCreate/firstOrCreate partout).
 *
 * Lancer avec :
 *   php artisan db:seed --database=tenant --class=Database\\Seeders\\DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Spécialités ----
        $gyneco = Specialty::firstOrCreate(['name' => 'Gynécologie'], ['color' => '#C0410C']);
        $pedia = Specialty::firstOrCreate(['name' => 'Pédiatrie'], ['color' => '#2F5D8A']);

        // ---- Médecins ----
        $userAka = User::updateOrCreate(
            ['email' => 'pr.aka@clinique-fame.ci'],
            ['name' => 'Pr Aka', 'phone' => '0700000010', 'password' => 'changeme123', 'role' => 'medecin']
        );
        $drAka = Doctor::updateOrCreate(
            ['user_id' => $userAka->id],
            ['specialty_id' => $gyneco->id, 'title' => 'Pr', 'teleconsultation_enabled' => true]
        );

        $userCoulibaly = User::updateOrCreate(
            ['email' => 'dr.coulibaly@clinique-fame.ci'],
            ['name' => 'Dr Coulibaly', 'phone' => '0700000011', 'password' => 'changeme123', 'role' => 'medecin']
        );
        $drCoulibaly = Doctor::updateOrCreate(
            ['user_id' => $userCoulibaly->id],
            ['specialty_id' => $pedia->id, 'title' => 'Dr']
        );

        // ---- Patientes ----
        $danielle = Patient::updateOrCreate(['phone' => '0701000001'], [
            'first_name' => 'Danielle', 'last_name' => '', 'sex' => 'F',
            'date_of_birth' => now()->subYears(29), 'created_via' => 'reception', 'identity_verified' => true,
        ]);
        $rose = Patient::updateOrCreate(['phone' => '0701000002'], [
            'first_name' => 'Rose', 'last_name' => '', 'sex' => 'F',
            'date_of_birth' => now()->subYears(34), 'created_via' => 'reception', 'identity_verified' => true,
        ]);
        $abigael = Patient::updateOrCreate(['phone' => '0701000003'], [
            'first_name' => 'Abigael', 'last_name' => '', 'sex' => 'F',
            'date_of_birth' => now()->subYears(26), 'created_via' => 'reception', 'identity_verified' => true,
        ]);
        $debora = Patient::updateOrCreate(['phone' => '0701000004'], [
            'first_name' => 'Debora', 'last_name' => '', 'sex' => 'F',
            'date_of_birth' => now()->subYears(31), 'created_via' => 'reception', 'identity_verified' => true,
        ]);
        $simone = Patient::updateOrCreate(['phone' => '0701000005'], [
            'first_name' => 'Simone', 'last_name' => '', 'sex' => 'F',
            'date_of_birth' => now()->subYears(38), 'created_via' => 'reception', 'identity_verified' => true,
        ]);

        // ---- Catégories + produits de stock ----
        $antalgiques = StockCategory::firstOrCreate(['domain' => 'pharmacie', 'name' => 'Antalgiques']);
        $antibiotiques = StockCategory::firstOrCreate(['domain' => 'pharmacie', 'name' => 'Antibiotiques']);
        $vaccins = StockCategory::firstOrCreate(['domain' => 'pharmacie', 'name' => 'Vaccins']);
        $soins = StockCategory::firstOrCreate(['domain' => 'consommable', 'name' => 'Soins et hygiène']);
        $denrees = StockCategory::firstOrCreate(['domain' => 'cuisine', 'name' => 'Denrées alimentaires']);

        Product::updateOrCreate(['name' => 'Paracétamol 500mg'], [
            'stock_category_id' => $antalgiques->id, 'unit' => 'comprimé',
            'quantity_on_hand' => 240, 'alert_threshold' => 60,
        ]);
        Product::updateOrCreate(['name' => 'Ibuprofène 400mg'], [
            'stock_category_id' => $antalgiques->id, 'unit' => 'comprimé',
            'quantity_on_hand' => 18, 'alert_threshold' => 30,
        ]);
        Product::updateOrCreate(['name' => 'Amoxicilline 500mg'], [
            'stock_category_id' => $antibiotiques->id, 'unit' => 'comprimé',
            'quantity_on_hand' => 0, 'alert_threshold' => 20,
        ]);
        Product::updateOrCreate(['name' => 'Vaccin ROR'], [
            'stock_category_id' => $vaccins->id, 'unit' => 'dose',
            'quantity_on_hand' => 16, 'alert_threshold' => 20,
        ]);
        Product::updateOrCreate(['name' => 'Gants latex, taille M'], [
            'stock_category_id' => $soins->id, 'unit' => 'boîte',
            'quantity_on_hand' => 14, 'alert_threshold' => 25,
        ]);
        Product::updateOrCreate(['name' => 'Compresses stériles'], [
            'stock_category_id' => $soins->id, 'unit' => 'paquet',
            'quantity_on_hand' => 31, 'alert_threshold' => 40,
        ]);
        Product::updateOrCreate(['name' => 'Riz local (sac 25kg)'], [
            'stock_category_id' => $denrees->id, 'unit' => 'sac',
            'quantity_on_hand' => 0, 'alert_threshold' => 10,
        ]);

        // ---- Rendez-vous du jour ----
        Appointment::updateOrCreate(
            ['patient_id' => $danielle->id, 'scheduled_at' => today()->setTime(9, 0)],
            ['doctor_id' => $drAka->id, 'specialty_id' => $gyneco->id, 'status' => 'confirmed',
                'type' => 'in_person', 'reason' => 'Suivi de grossesse', 'requested_by' => 'reception']
        );
        Appointment::updateOrCreate(
            ['patient_id' => $rose->id, 'scheduled_at' => today()->setTime(9, 45)],
            ['doctor_id' => $drAka->id, 'specialty_id' => $gyneco->id, 'status' => 'pending',
                'type' => 'in_person', 'reason' => 'Consultation gynéco', 'requested_by' => 'patient']
        );
        Appointment::updateOrCreate(
            ['patient_id' => $debora->id, 'scheduled_at' => today()->setTime(11, 15)],
            ['doctor_id' => $drCoulibaly->id, 'specialty_id' => $pedia->id, 'status' => 'confirmed',
                'type' => 'in_person', 'reason' => 'Vaccination', 'requested_by' => 'reception']
        );
        Appointment::updateOrCreate(
            ['patient_id' => $simone->id, 'scheduled_at' => today()->setTime(14, 30)],
            ['doctor_id' => $drAka->id, 'specialty_id' => $gyneco->id, 'status' => 'confirmed',
                'type' => 'teleconsultation', 'reason' => 'Téléconsultation', 'requested_by' => 'reception']
        );
        // Une demande en attente supplémentaire pour peupler le panneau de notifications
        Appointment::updateOrCreate(
            ['patient_id' => $abigael->id, 'scheduled_at' => today()->addDay()->setTime(10, 0)],
            ['doctor_id' => $drAka->id, 'specialty_id' => $gyneco->id, 'status' => 'pending',
                'type' => 'in_person', 'reason' => 'Bilan de fertilité', 'requested_by' => 'patient']
        );

        $this->command?->info('Données de démonstration FAME créées ✔');
    }
}
