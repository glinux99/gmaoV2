<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Attachment;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ChatController extends Controller
{


    /**
     * Page principale de la messagerie
     */
   public function index(Request $request, $conversationId = null)
    {

        $user = $request->user();

        // 1. Récupérer toutes les conversations auxquelles l'utilisateur appartient
        $conversations = $user->conversations()
            ->with(['users', 'messages' => function ($query) {
                // On récupère uniquement le dernier message pour l'aperçu dans la sidebar
                $query->latest()->limit(1);
            }])
            ->get();

        // 2. Déterminer la conversation active
        if (!$conversationId && $conversations->count() > 0) {
            // Si pas d'ID dans l'URL, on prend la première conversation de la liste
            $conversationId = $conversations->first()->id;
        }

        // 3. Formater les conversations pour le frontend Vue.js
        $formattedConversations = $conversations->map(function ($conv) use ($user) {
            $lastMessage = $conv->messages->first();

            // Si c'est un Message Direct (DM), on cherche l'autre utilisateur
            $otherUser = $conv->type === 'dm'
                ? $conv->users->where('id', '!=', $user->id)->first()
                : null;

            return [
                'id' => $conv->id,
                'type' => $conv->type, // 'dm' ou 'group'
                // Nom : Si DM, nom de l'autre personne. Sinon nom du groupe.
                'name' => $conv->type === 'dm' ? $otherUser->name ?? "Test" : $conv->name,
                // Avatar : Si DM, avatar de l'autre. Sinon null (on affichera une icône).
                'avatar' => $conv->type === 'dm' ? $otherUser->avatar ?? null  : null,
                'icon' => $conv->type === 'group' ? 'pi pi-hashtag' : null,
                'bg' => $conv->type === 'group' ? 'bg-indigo-600' : null,
                'status' => $otherUser ? 'online' : 'online', // À relier à votre système de présence
                'lastMsg' => $lastMessage ? $lastMessage->body : 'Démarrer la discussion...',
                'time' => $lastMessage ? $lastMessage->created_at->format('H:i') : '',
                'unread' => 0, // À implémenter avec une table pivot (ex: read_at)
            ];
        });

        // 4. Charger l'historique des messages UNIQUEMENT pour la conversation active
        $messages = [];
        if ($conversationId) {
            $activeConversation = Conversation::findOrFail($conversationId);

            // Vérifier que l'utilisateur a le droit d'accéder à cette conversation
            abort_unless($activeConversation->users->contains($user->id), 403);

            $messages = $activeConversation->messages()
                ->with('user')
                ->oldest() // Du plus vieux au plus récent pour l'affichage du chat
                ->get()
                ->map(function ($msg) use ($user) {
                    return [
                        'id' => $msg->id,
                        'text' => $msg->body,
                        'time' => $msg->created_at->format('H:i'),
                        'isMe' => $msg->user_id === $user->id,
                        'sender' => [
                            'id' => $msg->user->id,
                            'name' => $msg->user->name,
                            'avatar' => $msg->user->avatar,
                        ]
                    ];
                });
        }

        // 5. Renvoyer la vue avec Inertia
        return Inertia::render('Messages', [
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ],
            'conversations' => $formattedConversations,
            'activeConversationId' => $conversationId,
            'users'=>User::all(),
            // Inertia::lazy permet de ne recharger les messages QUE quand on change
            // de conversation (Partial Reloads) sans recharger toute la liste de gauche.
            'initialMessages' => Inertia::lazy(fn () => $messages),
        ]);
    }
      public function getConversations(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['users' => function ($q) use ($user) {
                $q->select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.status')
                    ->withPivot('last_read_at', 'is_muted');
            }])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('messages.created_at', '>', DB::raw('(SELECT last_read_at FROM conversation_user WHERE conversation_id = messages.conversation_id AND user_id = ' . $user->id . ')'));
            }])
            ->orderByDesc('updated_at')
            ->get();

        $formatted = $conversations->map(function ($conv) use ($user) {
            $isDm = $conv->type === 'dm';
            $otherUser = $isDm ? $conv->users->firstWhere('id', '!=', $user->id) : null;

            $lastMsg = $conv->messages()->latest()->first();
            $lastMessage = $lastMsg ? $lastMsg->body : null;
            $lastTime = $lastMsg ? $lastMsg->created_at->format('H:i') : null;

            return [
                'id' => $conv->id,
                'type' => $conv->type,
                'name' => $isDm ? $otherUser->name : $conv->name,
                'icon' => $conv->type === 'channel' ? ($conv->is_private ? 'pi pi-lock' : 'pi pi-hashtag') : null,
                'unread' => $conv->unread_count,
                'description' => $conv->description,
                'is_private' => $conv->is_private,
                'user' => $isDm ? [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar ?? $otherUser->profile_photo_url,
                    'status' => $otherUser->status ?? 'offline',
                    'role' => $otherUser->position ?? 'Utilisateur',
                ] : null,
                'last_message' => $lastMessage,
                'time' => $lastTime,
                'created_at' => $conv->created_at,
                'updated_at' => $conv->updated_at,
                'creator' => $conv->creator ? [
                    'id' => $conv->creator->id,
                    'name' => $conv->creator->name,
                ] : null,
            ];
        });

        return response()->json($formatted);
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:channel,dm',
            'name' => 'required_if:type,channel|string|max:100|unique:conversations,name',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean',
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id',
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            $conversation = Conversation::create([
                'name' => $validated['type'] === 'channel' ? $validated['name'] : null,
                'type' => $validated['type'],
                'is_private' => $validated['is_private'] ?? false,
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $usersIds = array_unique(array_merge($validated['users'], [$user->id]));
            $now = now();
            foreach ($usersIds as $uid) {
                $conversation->users()->attach($uid, ['last_read_at' => $now]);
            }

            DB::commit();

            $conversation->load('users');

            return response()->json([
                'message' => 'Conversation créée avec succès',
                'conversation' => $conversation
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la création de la conversation'], 500);
        }
    }

    /**
     * Mettre à jour un canal (nom, description, privé)
     */
    public function updateConversation(Request $request, Conversation $conversation)
    {
        if ($conversation->type !== 'channel') {
            return response()->json(['error' => 'Seuls les canaux peuvent être modifiés'], 403);
        }

        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:conversations,name,' . $conversation->id,
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean',
        ]);

        $conversation->update($validated);

        return response()->json([
            'message' => 'Canal mis à jour',
            'conversation' => $conversation
        ]);
    }

    /**
     * Quitter ou supprimer une conversation
     */
    public function deleteConversation(Conversation $conversation)
    {
        $user = request()->user();

        if ($conversation->type === 'channel' && $conversation->created_by === $user->id) {
            foreach ($conversation->messages as $msg) {
                foreach ($msg->attachments as $att) {
                    Storage::disk($att->disk)->delete($att->file_path);
                }
            }
            $conversation->delete();
            return response()->json(['message' => 'Canal supprimé définitivement']);
        }

        $conversation->users()->detach($user->id);
        return response()->json(['message' => 'Vous avez quitté la conversation']);
    }

    /**
     * Marquer une conversation comme lue
     */

    // ==================== MESSAGES ====================

    /**
     * Récupérer les messages d'une conversation (paginés)
     */
    public function getMessages(Conversation $conversation, Request $request)
    {
        $this->authorize('view', $conversation);

        // CORRECTION : utiliser les deux-points pour min et max
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);
        $limit = $request->input('limit', 50);

        $messages = $conversation->messages()
            ->with(['user:id,name,avatar,email,status', 'attachments', 'reactions.user:id,name'])
            ->orderByDesc('created_at')
            ->paginate($limit);

        $user = $request->user();
        $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Envoyer un nouveau message (texte + fichiers joints)
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('send', $conversation);

        $validated = $request->validate([
            'body' => 'nullable|string|max:5000',
            'parent_id' => 'nullable|exists:messages,id',
            'attachments' => 'array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,mp3,mp4',
        ]);

        if (empty($validated['body']) && empty($request->file('attachments'))) {
            throw ValidationException::withMessages(['message' => 'Le message ou une pièce jointe est requis']);
        }

        DB::beginTransaction();
        try {
            $message = $conversation->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['body'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/' . $conversation->id, 'public');
                    $message->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'disk' => 'public',
                    ]);
                }
            }

            $conversation->touch();

            DB::commit();

            $message->load(['user', 'attachments']);

            return response()->json([
                'message' => 'Message envoyé',
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l’envoi du message'], 500);
        }
    }

    /**
     * Modifier un message existant
     */
    public function updateMessage(Request $request, Message $message)
    {
        $this->authorize('update', $message);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message->update([
            'body' => $validated['body'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json([
            'message' => 'Message modifié',
            'data' => $message->fresh(['user', 'attachments'])
        ]);
    }

    /**
     * Supprimer un message
     */
    public function deleteMessage(Message $message)
    {
        $this->authorize('delete', $message);

        foreach ($message->attachments as $att) {
            Storage::disk($att->disk)->delete($att->file_path);
        }
        $message->delete();

        return response()->json(['message' => 'Message supprimé']);
    }

    // ==================== PIÈCES JOINTES ====================

    /**
     * Télécharger une pièce jointe
     */
    public function downloadAttachment(Attachment $attachment)
    {
        $message = $attachment->message;
        $this->authorize('view', $message->conversation);

        if (!Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        // return Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Supprimer une pièce jointe
     */
    public function deleteAttachment(Attachment $attachment)
    {
        $this->authorize('delete', $attachment->message);

        Storage::disk($attachment->disk)->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Pièce jointe supprimée']);
    }

    // ==================== RÉACTIONS ====================

    /**
     * Ajouter, modifier ou retirer une réaction
     */
    public function toggleReaction(Request $request, Message $message)
    {
        $validated = $request->validate([
            'reaction' => 'required|string|max:10',
        ]);

        $user = $request->user();
        $reaction = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction && $reaction->reaction === $validated['reaction']) {
            $reaction->delete();
            return response()->json(['message' => 'Réaction retirée']);
        } elseif ($reaction) {
            $reaction->update(['reaction' => $validated['reaction']]);
            return response()->json(['message' => 'Réaction mise à jour']);
        } else {
            $newReaction = MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'reaction' => $validated['reaction'],
            ]);
            return response()->json(['message' => 'Réaction ajoutée', 'data' => $newReaction]);
        }
    }

    // ==================== GESTION DES MEMBRES ====================

    /**
     * Récupérer les membres d'un canal
     */


    /**
     * Retirer un membre d'un canal
     */


    /**
     * Mettre à jour le rôle d'un membre (nécessite une colonne 'role' dans la table pivot)
     */


    // ==================== RECHERCHE D'UTILISATEURS ====================

    /**
     * Rechercher des utilisateurs
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'email', 'avatar', 'status']);

        $users->transform(function ($user) {
            $user->avatar = $user->avatar ?? $user->profile_photo_url;
            return $user;
        });

        return response()->json($users);
    }

    // ==================== UTILITAIRES ====================

    /**
     * Récupérer les fichiers partagés dans une conversation
     */
    public function getSharedFiles(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $files = Attachment::whereHas('message', function ($q) use ($conversation) {
            $q->where('conversation_id', $conversation->id);
        })->latest()->get();

        return response()->json($files);
    }

    /**
     * Obtenir le nombre total de messages non lus
     */
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        $totalUnread = 0;

        foreach ($user->conversations as $conv) {
            $lastRead = $conv->users->where('id', $user->id)->first()->pivot->last_read_at ?? now();
            $unread = $conv->messages()->where('created_at', '>', $lastRead)->count();
            $totalUnread += $unread;
        }

        return response()->json(['unread_count' => $totalUnread]);
    }


    /**
 * Marquer une conversation comme lue
 */
public function markAsRead(Conversation $conversation)
{
    $user = request()->user();
    $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    return response()->json(['message' => 'Conversation marquée comme lue']);
}

/**
 * Récupérer les membres d'un canal
 */
public function getMembers(Conversation $conversation)
{
    if ($conversation->type !== 'channel') {
        return response()->json(['error' => 'Cette conversation n\'est pas un canal'], 400);
    }
    $this->authorize('view', $conversation);
    $members = $conversation->users()
        ->select('users.id', 'users.name', 'users.avatar', 'users.email', 'users.status')
        ->get()
        ->map(function ($user) use ($conversation) {
            $role = 'member';
            if ($user->id === $conversation->created_by) {
                $role = 'admin';
            } elseif ($user->pivot->role ?? false) {
                $role = $user->pivot->role;
            }
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? $user->profile_photo_url,
                'email' => $user->email,
                'status' => $user->status ?? 'offline',
                'role' => $role,
            ];
        });
    return response()->json($members);
}

/**
 * Ajouter des membres à un canal (admin uniquement)
 */
public function addMembers(Request $request, Conversation $conversation)
{
    if ($conversation->type !== 'channel') {
        return response()->json(['error' => 'Seuls les canaux peuvent accueillir des membres'], 400);
    }

    // Vérifier explicitement si l'utilisateur est le créateur
    $user = $request->user();
    if ($conversation->created_by !== $user->id) {
        return response()->json(['error' => 'Seul le créateur du canal peut ajouter des membres'], 403);
    }

    $validated = $request->validate([
        'users' => 'required|array|min:1',
        'users.*' => 'exists:users,id',
    ]);

    $currentUserIds = $conversation->users()->pluck('user_id')->toArray();
    $newUserIds = array_diff($validated['users'], $currentUserIds);

    if (empty($newUserIds)) {
        return response()->json(['message' => 'Aucun nouvel utilisateur à ajouter'], 200);
    }

    $now = now();
    foreach ($newUserIds as $uid) {
        $conversation->users()->attach($uid, ['last_read_at' => $now]);
    }

    return response()->json(['message' => 'Membres ajoutés avec succès']);
}

/**
 * Retirer un membre d'un canal (admin uniquement)
 */
public function removeMember(Conversation $conversation, User $user)
{
    if ($conversation->type !== 'channel') {
        return response()->json(['error' => 'Seuls les canaux peuvent gérer des membres'], 400);
    }
    $this->authorize('manageMembers', $conversation);
    if ($user->id === $conversation->created_by) {
        return response()->json(['error' => 'Vous ne pouvez pas retirer le créateur du canal'], 403);
    }
    $conversation->users()->detach($user->id);
    return response()->json(['message' => 'Membre retiré du canal']);
}

/**
 * Mettre à jour le rôle d'un membre (admin uniquement)
 * Nécessite une colonne 'role' dans la table pivot conversation_user
 */
public function updateMemberRole(Request $request, Conversation $conversation, User $user)
{
    if ($conversation->type !== 'channel') {
        return response()->json(['error' => 'Seuls les canaux peuvent définir des rôles'], 400);
    }
    $this->authorize('manageMembers', $conversation);
    $validated = $request->validate([
        'role' => 'required|in:admin,moderator,member',
    ]);
    if (!$conversation->users()->where('user_id', $user->id)->exists()) {
        return response()->json(['error' => 'Cet utilisateur n\'est pas membre du canal'], 404);
    }
    if ($user->id === $conversation->created_by && $validated['role'] !== 'admin') {
        return response()->json(['error' => 'Le créateur du canal ne peut pas être rétrogradé'], 403);
    }
    $conversation->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);
    return response()->json(['message' => 'Rôle mis à jour']);
}
}
