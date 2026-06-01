<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\Campaign;
use App\Models\SentEmail;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Jobs\SendCampaignEmails;

class NewsletterController extends Controller
{
    /**
     * Page principale (gère les trois onglets)
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'subscribers');
        $search = $request->input('search', '');

        $data = [
            'currentTab' => $tab,
            'search'     => $search,
            'settings'   => Setting::all()->pluck('value', 'key')->toArray(),
        ];

        // Chargement des abonnés (Subscribers)
        $subQuery = Subscriber::query();
        if ($tab === 'subscribers' && $search) {
            $subQuery->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $data['subscribers'] = $subQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'subscribers_page')->withQueryString();

        // Pour le PickList (sélection des destinataires dans les campagnes)
        $data['subscribersAll'] = Subscriber::select('id', 'email', 'name')->orderBy('email')->get();

        // Chargement des campagnes (Campaigns)
        $campQuery = Campaign::with('subscribers');
        if ($tab === 'campaigns' && $search) {
            $campQuery->where('subject', 'like', "%{$search}%");
        }
        $data['campaigns'] = $campQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'campaigns_page')->withQueryString();

        // Chargement des emails envoyés (Sent Emails)
        $sentQuery = SentEmail::with('campaign');
        if ($tab === 'sent' && $search) {
            $sentQuery->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }
        $data['sentEmails'] = $sentQuery->orderBy('sent_at', 'desc')->paginate(10, ['*'], 'sent_page')->withQueryString();

        return Inertia::render('Admin/Newsletters', $data);
    }

    // ===================== ABONNÉS =====================

    /**
     * Ajouter un abonné
     */
    public function subscriberStore(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email|unique:subscribers,email',
            'name'     => 'nullable|string|max:255',
            'is_active'=> 'boolean',
        ]);

        Subscriber::create($validated + ['subscribed_at' => now()]);

        return redirect()->route('newsletters.index', ['tab' => 'subscribers'])
            ->with('success', 'Abonné ajouté avec succès.');
    }

    /**
     * Mettre à jour un abonné
     */
    public function subscriberUpdate(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'email'    => 'required|email|unique:subscribers,email,' . $subscriber->id,
            'name'     => 'nullable|string|max:255',
            'is_active'=> 'boolean',
        ]);

        $subscriber->update($validated);

        return redirect()->route('newsletters.index', ['tab' => 'subscribers'])
            ->with('success', 'Abonné mis à jour.');
    }

    /**
     * Supprimer un abonné
     */
    public function subscriberDestroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()->route('newsletters.index', ['tab' => 'subscribers'])
            ->with('success', 'Abonné supprimé.');
    }

    /**
     * Importer des abonnés depuis un fichier CSV
     */
    public function importSubscribers(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $emailIndex = array_search('email', $header);
        $nameIndex  = array_search('name', $header);

        if ($emailIndex === false) {
            return back()->withErrors(['csv' => 'Le fichier CSV doit contenir une colonne "email".']);
        }

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $email = trim($row[$emailIndex]);
            if (empty($email)) continue;
            $name = $nameIndex !== false ? trim($row[$nameIndex]) : null;
            Subscriber::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'subscribed_at' => now(), 'is_active' => true]
            );
            $imported++;
        }
        fclose($handle);

        return redirect()->route('newsletters.index', ['tab' => 'subscribers'])
            ->with('success', "{$imported} abonné(s) importé(s).");
    }

    /**
     * Récupérer tous les abonnés (pour le composant PickList)
     */
    public function subscribersAll()
    {
        return Subscriber::select('id', 'email', 'name')
            ->orderBy('email')
            ->get();
    }

    // ===================== CAMPAGNES =====================

    /**
     * Créer une campagne
     */
    public function campaignStore(Request $request)
    {
        $validated = $request->validate([
            'subject'        => 'required|string|max:255',
            'content'        => 'nullable|string',
            'recipient_mode' => 'required|in:all,custom',
            'recipients'     => 'array',
            'recipients.*'   => 'integer|exists:subscribers,id',
            'scheduled_at'   => 'nullable|date',
        ]);

        $campaign = Campaign::create([
            'subject'        => $validated['subject'],
            'content'        => $validated['content'] ?? '',
            'recipient_mode' => $validated['recipient_mode'],
            'scheduled_at'   => $validated['scheduled_at'] ?? null,
            'status'         => 'draft',
        ]);

        if ($validated['recipient_mode'] === 'custom' && !empty($validated['recipients'])) {
            $campaign->subscribers()->attach($validated['recipients']);
        }

        return redirect()->route('newsletters.index', ['tab' => 'campaigns'])
            ->with('success', 'Campagne créée avec succès.');
    }

    /**
     * Mettre à jour une campagne
     */
    public function campaignUpdate(Request $request, Campaign $campaign)
    {
        if ($campaign->status === 'sent' || $campaign->status === 'sending') {
            return back()->withErrors(['status' => 'Impossible de modifier une campagne envoyée ou en cours.']);
        }

        $validated = $request->validate([
            'subject'        => 'required|string|max:255',
            'content'        => 'nullable|string',
            'recipient_mode' => 'required|in:all,custom',
            'recipients'     => 'array',
            'recipients.*'   => 'integer|exists:subscribers,id',
            'scheduled_at'   => 'nullable|date',
        ]);

        $campaign->update([
            'subject'        => $validated['subject'],
            'content'        => $validated['content'] ?? $campaign->content,
            'recipient_mode' => $validated['recipient_mode'],
            'scheduled_at'   => $validated['scheduled_at'] ?? $campaign->scheduled_at,
        ]);

        if ($validated['recipient_mode'] === 'custom') {
            $campaign->subscribers()->sync($validated['recipients'] ?? []);
        } else {
            $campaign->subscribers()->detach();
        }

        return redirect()->route('newsletters.index', ['tab' => 'campaigns'])
            ->with('success', 'Campagne mise à jour.');
    }

    /**
     * Supprimer une campagne
     */
    public function campaignDestroy(Campaign $campaign)
    {
        if ($campaign->status === 'sending') {
            return back()->withErrors(['status' => 'Impossible de supprimer une campagne en cours d\'envoi.']);
        }

        $campaign->delete();

        return redirect()->route('newsletters.index', ['tab' => 'campaigns'])
            ->with('success', 'Campagne supprimée.');
    }

    /**
     * Lancer l'envoi d'une campagne (asynchrone via job)
     */
    public function sendCampaign(Campaign $campaign)
    {
        if ($campaign->status === 'sent' || $campaign->status === 'sending') {
            return back()->withErrors(['status' => 'Campagne déjà envoyée ou en cours.']);
        }

        $campaign->update(['status' => 'sending']);

        if ($campaign->recipient_mode === 'all') {
            $recipients = Subscriber::where('is_active', true)->get();
        } else {
            $recipients = $campaign->subscribers()->where('is_active', true)->get();
        }

        // Dispatch du job d'envoi
        dispatch(new SendCampaignEmails($campaign, $recipients));

        return redirect()->route('newsletters.index', ['tab' => 'campaigns'])
            ->with('success', 'Campagne en cours d\'envoi.');
    }

    // ===================== EMAILS ENVOYÉS =====================

    /**
     * Répondre à un email (depuis l'onglet "Envoyés")
     */
    public function replyToEmail(Request $request)
{
    $validated = $request->validate([
        'to_email'   => 'required|email',
        'subject'    => 'required|string|max:255',
        'body'       => 'required|string',
        'original_id'=> 'nullable|integer|exists:sent_emails,id',
    ]);

    // Envoi de l'email
    Mail::html($validated['body'], function ($message) use ($validated) {
        $message->to($validated['to_email'])
                ->subject($validated['subject'])
                ->from(config('mail.from.address'), config('mail.from.name'));
    });

    // Recherche de l'abonné correspondant (optionnel)
    $subscriber = Subscriber::where('email', $validated['to_email'])->first();

    SentEmail::create([
        'recipient_email' => $validated['to_email'],
        'subject'         => $validated['subject'],
        'body'            => $validated['body'],
        'sent_at'         => now(),
        'campaign_id'     => null,
        'subscriber_id'   => $subscriber?->id, // null si non trouvé
    ]);

    return redirect()->route('newsletters.index', ['tab' => 'sent'])
        ->with('success', 'Réponse envoyée avec succès.');
}
}
