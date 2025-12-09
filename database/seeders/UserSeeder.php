<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rôles attendus (créés par RoleSeeder)
        $roleNames = ['superadmin', 'admin', 'operator', 'technician', 'magasinier', 'visitor'];
        $roles = Role::whereIn('name', $roleNames)->pluck('name')->all();

        // Comptes pilotes par rôle (incluant votre superadmin existant)
        $pilotUsers = [
            [
                'name' => 'Superadmin',
                'email' => 'genesiskikimba@gmail.com',
                'password' => Hash::make('genesiskikimba@gmail.com'),
                'role' => 'superadmin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@operator.com',
                'password' => Hash::make('operator'),
                'role' => 'operator',
            ],
            [
                'name' => 'Technician',
                'email' => 'technician@technician.com',
                'password' => Hash::make('technician'),
                'role' => 'technician',
            ],
            [
                'name' => 'Magasinier',
                'email' => 'magasinier@magasinier.com',
                'password' => Hash::make('magasinier'),
                'role' => 'magasinier',
            ],
            [
                'name' => 'Visitor',
                'email' => 'visitor@visitor.com',
                'password' => Hash::make('visitor'),
                'role' => 'visitor',
            ],
        ];

        foreach ($pilotUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'provider_name' => null,
                    'provider_id' => null,
                ]
            );

            // Assigner le rôle si présent dans la base
            if (in_array($data['role'], $roles, true)) {
                $user->syncRoles([$data['role']]);
            }

            // Ajouter/rafraîchir un avatar de test via Spatie Media (pravatar)
            try {
                $user->clearMediaCollection('avatar');
                $user->addMediaFromUrl('https://i.pravatar.cc/300?u=' . urlencode($user->email))
                    ->usingFileName('avatar-' . $user->id . '.jpg')
                    ->toMediaCollection('avatar');
            } catch (\Throwable $e) {
                // Ignorer les erreurs d'avatar durant le seeding
            }
        }

        // Générer 50 utilisateurs aléatoires avec un rôle aléatoire parmi ceux existants
        if (count($roles) > 0) {
            User::factory()
                ->count(100)
                ->create()
                ->each(function (User $user) use ($roles) {
                    $role = $roles[array_rand($roles)];
                    $user->assignRole($role);

                    // Ajouter un avatar de test
                    try {
                        $user->addMediaFromUrl('https://i.pravatar.cc/300?u=' . urlencode($user->email))
                            ->usingFileName('avatar-' . $user->id . '.jpg')
                            ->toMediaCollection('avatar');
                    } catch (\Throwable $e) {
                        // Ignorer en seed
                    }
                });
        }
    }
}
