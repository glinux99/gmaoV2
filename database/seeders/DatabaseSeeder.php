<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
                // 2. Ensuite les types d'équipements (Nécessaire pour le catalogue)

            // 1. D'abord les dépendances (Permission, Role, User, Region)
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,

        ]);
            $admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'online',
            'last_activity_at' => now(),
        ]);
        // Utilisateurs mock
        $usersData = [
            ['name' => 'Alice Dubois', 'email' => 'alice@example.com', 'position' => 'Directrice RH', 'status' => 'online'],
            ['name' => 'Marc Lemaire', 'email' => 'marc@example.com', 'position' => 'Tech Lead', 'status' => 'offline'],
            ['name' => 'Sophie Martin', 'email' => 'sophie@example.com', 'position' => 'Commerciale', 'status' => 'busy'],
            ['name' => 'Lucas Petit', 'email' => 'lucas@example.com', 'position' => 'Designer', 'status' => 'online'],
            ['name' => 'Emma Blanc', 'email' => 'emma@example.com', 'position' => 'Marketing', 'status' => 'away'],
        ];

        $users = collect();
        foreach ($usersData as $data) {
            $users->push(User::create(array_merge($data, [
                'password' => Hash::make('password'),
                'last_activity_at' => now(),
            ])));
        }

        $allUsers = $users->push($admin);

        // Canaux
        $general = Conversation::create([
            'name' => 'Général',
            'type' => 'channel',
            'description' => 'Annonces de l’entreprise.',
            'created_by' => $admin->id,
        ]);

        $projetAlpha = Conversation::create([
            'name' => 'Projet Alpha',
            'type' => 'channel',
            'is_private' => true,
            'description' => 'Discussions confidentielles.',
            'created_by' => $admin->id,
        ]);

        $design = Conversation::create([
            'name' => 'Design Team',
            'type' => 'channel',
            'description' => 'Veille et assets graphiques.',
            'created_by' => $admin->id,
        ]);

        $supportIT = Conversation::create([
            'name' => 'Support IT',
            'type' => 'channel',
            'description' => 'Demandes techniques internes.',
            'created_by' => $admin->id,
        ]);

        // Attacher tous les users aux canaux publics
        foreach ([$general, $design, $supportIT] as $channel) {
            foreach ($allUsers as $user) {
                $channel->users()->attach($user->id, ['last_read_at' => now()]);
            }
        }
        $projetAlpha->users()->attach([$admin->id, $users[1]->id], ['last_read_at' => now()]);

        // Messages directs
        foreach ($users as $user) {
            $dm = Conversation::create(['type' => 'dm', 'created_by' => $admin->id]);
            $dm->users()->attach([$admin->id, $user->id], ['last_read_at' => now()]);

            for ($i = 0; $i < 10; $i++) {
                $isMe = $i % 2 == 0;
                Message::create([
                    'conversation_id' => $dm->id,
                    'user_id' => $isMe ? $admin->id : $user->id,
                    'body' => $isMe ? 'Voici le document demandé.' : 'Bonjour, pourrions-nous faire un point ?',
                    'created_at' => now()->subMinutes(rand(1, 120)),
                ]);
            }
        }

        // Messages dans les canaux
        $channelMessages = [
            ['conversation' => $general, 'user' => $admin, 'body' => 'Bienvenue à tous sur le canal Général !'],
            ['conversation' => $general, 'user' => $users[0], 'body' => 'Merci, ravie d’être là.'],
            ['conversation' => $design, 'user' => $users[3], 'body' => 'Nouvelle charte graphique disponible dans les fichiers.'],
            ['conversation' => $supportIT, 'user' => $users[1], 'body' => 'Mise à jour des serveurs prévue ce soir à 22h.'],
        ];

        foreach ($channelMessages as $msg) {
            Message::create([
                'conversation_id' => $msg['conversation']->id,
                'user_id' => $msg['user']->id,
                'body' => $msg['body'],
                'created_at' => now()->subHours(rand(1, 48)),
            ]);
        }

        // Exemple de pièce jointe
        $lastMsg = Message::latest()->first();
        Attachment::create([
            'message_id' => $lastMsg->id,
            'file_name' => 'specifications.pdf',
            'file_path' => 'attachments/specifications.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048000,
            'disk' => 'public',
        ]);

        // Exemple de réaction
        MessageReaction::create([
            'message_id' => $lastMsg->id,
            'user_id' => $admin->id,
            'reaction' => 'Ok',
        ]);

    }
}
