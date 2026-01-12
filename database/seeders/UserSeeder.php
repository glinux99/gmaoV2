<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
                'name' => 'Superadmin',
                'email' => 'genesiskikimba@gmail.com',
                'password' => 'genesiskikimba@gmail.com', // Sera haché plus bas
                'role' => 'superadmin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => 'admin',
                'role' => 'admin',
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@operator.com',
                'password' => 'operator',
                'role' => 'operator',
            ],
            [
                'name' => 'Technician',
                'email' => 'technician@technician.com',
                'password' => 'technician',
                'role' => 'technician',
            ],
            [
                'name' => 'Magasinier',
                'email' => 'magasinier@magasinier.com',
                'password' => 'magasinier',
                'role' => 'magasinier',
            ],
            [
                'name' => 'Visitor',
                'email' => 'visitor@visitor.com',
                'password' => 'visitor',
                'role' => 'visitor',
            ],
        ];

        foreach ($pilotUsers as $data) {
            // Utilisation de updateOrCreate pour s'assurer que les infos sont à jour
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            // Assigner le rôle
            if (in_array($data['role'], $roles)) {
                $user->syncRoles([$data['role']]);
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
