<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Region;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TechnicianImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $regionName = $row['region'] ?? null;
        $region = Region::where('designation', $regionName)->orWhere('code', $regionName)->first();

        $user = User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['noms'],
                'password' => Hash::make($row['password'] ?? 'password'),
                'fonction' => $row['fonction'] ?? null,
                'region_id' => $region ? $region->id : null,
                'email_verified_at' => now(),
            ]
        );

        $roleName = $row['role'] ?? 'technician';
        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        return $user;
    }

    public function rules(): array
    {
        return [
            'noms' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            // 'email' => 'required|email|max:255|unique:users,email', // Unique rule is handled by updateOrCreate
        ];
    }

    public function customValidationMessages()
    {
        return [
            'noms.required' => 'Le nom du technicien est obligatoire.',
            'email.required' => 'L\'email du technicien est obligatoire.',
            'email.email' => 'L\'email fourni n\'est pas une adresse valide.',
            // 'email.unique' => 'Un utilisateur avec cet email existe déjà.',
        ];
    }
}
