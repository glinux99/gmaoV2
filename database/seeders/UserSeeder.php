<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Region;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Définition et création des rôles de sécurité
        $roleNames = ['superadmin', 'admin', 'operator', 'technician', 'magasinier', 'visitor'];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        // Récupérer tous les noms de rôles pour la validation plus bas
        $roles = Role::pluck('name')->all();

        // 2. Configuration des comptes administratifs et pilotes
        $pilotUsers = [
            [
                'name' => 'Daniel UMIRAMBE',
                'email' => 'dumirambe@virunga.org',
                'password' => 'dumirambe@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau',
                'region' => 'Goma',
                'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'BAHATI MANGALA Guillaume',
                'email' => 'gmangala@virunga.org',
                'password' => 'gmangala@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau Adj.',
                'region' => 'Goma',
                'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'SUKAKA NEMOTI Espérance',
                'email' => 'enemoti@virunga.org',
                'password' => 'enemoti@virunga.org',
                'fonction' => 'Superviseur sous-station',
                'region' => 'Goma',
                'role' => 'technician',
            ],
            [
                'name' => 'BWIRUKE HABIMANA Georges',
                'email' => 'ghabimana@virunga.org',
                'password' => 'ghabimana@virunga.org',
                'fonction' => 'Superviseur Maintenance',
                'region' => 'Goma',
                'role' => 'technician',
            ],
            [
                'name' => 'TCHANGWI LYADUNDA Jonathan',
                'email' => 'jlyadunda@virunga.org',
                'password' => 'jlyadunda@virunga.org',
                'fonction' => 'Superviseur Intervention',
                'region' => 'Goma',
                'role' => 'technician',
            ],
            [
                'name' => 'TUMUSIFI SINZABAKWIRA T.',
                'email' => 'tsinzabakwira@virunga.org',
                'password' => 'tsinzabakwira@virunga.org',
                'fonction' => 'Superviseur raccordement',
                'region' => 'Goma',
                'role' => 'technician',
            ],
            [
                'name' => 'MAGENDA BAGABO DAVY',
                'email' => 'davybagabo@virunga.org',
                'password' => 'davybagabo@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau',
                'region' => 'Matebe',
                   'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'MAHONGO PATRICK',
                'email' => 'patrickmahongo@virunga.org',
                'password' => 'patrickmahongo@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau Adj.',
                'region' => 'Matebe',
                   'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'DAVID BIN ABEDI',
                'email' => 'abedibin@virunga.org',
                'password' => 'abedibin@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau',
                'region' => 'Lubero',
                   'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'Jonathan TCHANGWI',
                'email' => 'tchangwij@virunga.org',
                'password' => 'tchangwij@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau Adj.',
                'region' => 'Lubero',
                   'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'ERIC MUBANGO',
                'email' => 'mubangoeric@virunga.org',
                'password' => 'mubangoeric@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau',
                'region' => 'Mutwanga',
                   'role' => ['superadmin', 'technician'],
            ],
            [
                'name' => 'JANVIER KAHULA',
                'email' => 'kahulajanvier@virunga.org',
                'password' => 'kahulajanvier@virunga.org',
                'fonction' => 'Resp. Exploitation Réseau Adj.',
                'region' => 'Mutwanga',
                   'role' => ['superadmin', 'technician'],
            ],
              [
                'name' => 'Administrator',
                'email' => 'admin@virunga.org',
                'password' => 'admin@virunga.org',
                'fonction' => 'Administrateur',
                'region' => 'Goma',
                   'role' => ['superadmin'],
            ],
        ];

        foreach ($pilotUsers as $data) {
            // Utilisation de updateOrCreate pour s'assurer que les infos sont à jour
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'fonction' => $data['fonction'],
                    'region_id' => Region::where('designation', 'like', '%' . $data['region'] . '%')->first()->id ?? null,
                    'email_verified_at' => now(),
                ]
            );

            // Assigner le rôle
            if (is_array($data['role'])) {
                $user->syncRoles($data['role']);
            } elseif (in_array($data['role'], $roles)) {
                $user->syncRoles([$data['role']]);
            } else {
                // Gérer le cas où le rôle n'est pas un tableau et n'est pas dans la liste des rôles connus
                // Par exemple, logguer une erreur ou assigner un rôle par défaut
            }

            // Gestion de l'avatar (Spatie MediaLibrary)
            $this->addAvatarToUser($user);
        }

        // 3. Génération d'utilisateurs aléatoires (Factory)
        User::factory()
            ->count(5)
            ->create()
            ->each(function (User $user) use ($roles) {
                // Assigner un rôle aléatoire (sauf superadmin pour la sécurité)
                $randomRoles = array_diff($roles, ['superadmin']);
                $user->assignRole($randomRoles[array_rand($randomRoles)]);

                $this->addAvatarToUser($user);
            });
    }

    /**
     * Méthode helper pour ajouter un avatar proprement
     */
    private function addAvatarToUser(User $user)
    {
        try {
            // On ne télécharge que si l'utilisateur n'a pas déjà d'avatar pour gagner du temps
            if ($user->getMedia('avatar')->isEmpty()) {
                $user->addMediaFromUrl('https://i.pravatar.cc/300?u=' . urlencode($user->email))
                    ->usingFileName('avatar-' . $user->id . '.jpg')
                    ->toMediaCollection('avatar');
            }
        } catch (\Throwable $e) {
            // On ignore silencieusement si pas d'internet ou erreur de téléchargement
        }
    }
}
