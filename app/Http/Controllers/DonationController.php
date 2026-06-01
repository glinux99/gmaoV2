<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DonationController extends Controller
{
    /**
     * Affiche la liste des dons (donateurs) avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $query = Donation::query();

        // Recherche globale (prénom, nom, email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Filtre par statut de contact
        if ($request->has('contacted') && $request->contacted !== null) {
            $query->where('contacted', filter_var($request->contacted, FILTER_VALIDATE_BOOLEAN));
        }

        // Tri par 'order' (drag & drop) puis par date de création décroissante
        $donations = $query->orderBy('order', 'asc')
                           ->orderBy('created_at', 'desc')
                           ->paginate(15)
                           ->withQueryString();

        return Inertia::render('Admin/Donations', [
            'donors' => $donations, // pour correspondre à la prop 'donors' du composant Vue
            'filters' => $request->only(['search', 'contacted']),
        ]);
    }

    /**
     * Enregistre un nouveau don (depuis la modale de don publique ou admin).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amountType'    => 'required|in:once,monthly',
            'amount'        => 'required|numeric|min:1',
            'paymentMethod' => 'required|string|in:paypal,stripe,transfer',
            'firstName'     => 'required|string|max:255',
            'lastName'      => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'taxReceipt'    => 'boolean',
            'newsletter'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Déterminer l'ordre max actuel
        $maxOrder = Donation::max('order') ?? 0;

        $donation = Donation::create([
            'first_name'     => $request->firstName,
            'last_name'      => $request->lastName,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'amount'         => $request->amount,
            'type'           => $request->amountType,       // 'once' ou 'monthly'
            'payment_method' => $request->paymentMethod,
            'tax_receipt'    => $request->taxReceipt,
            'status'         => 'pending',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            // Nouveaux champs
            'newsletter'     => $request->newsletter ?? false,
            'order'          => $maxOrder + 1,
            'donation_date'  => now(),
            'contacted'      => false,
        ]);

        // Intégration du paiement (à implémenter selon votre passerelle)
        // ...

        return redirect()->back()->with('success', 'Don enregistré avec succès.');
    }

    /**
     * Met à jour un don existant (depuis l'interface admin).
     */
    public function update(Request $request, Donation $donation)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'amount'        => 'required|numeric|min:1',
            'donation_type' => 'required|in:once,monthly',
            'donation_date' => 'nullable|date',
            'contacted'     => 'boolean',
            'tax_receipt'   => 'boolean',
            'newsletter'    => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        // Renommer 'donation_type' en 'type' pour correspondre à la base
        $data['type'] = $data['donation_type'];
        unset($data['donation_type']);

        $donation->update($data);

        return redirect()->back()->with('success', 'Donateur mis à jour.');
    }

    /**
     * Supprime un don.
     */
    public function destroy(Donation $donation)
    {
        $donation->delete();
        return redirect()->back()->with('success', 'Donateur supprimé.');
    }

    /**
     * Marque un don comme "contacté".
     */
    public function markContacted(Donation $donation)
    {
        $donation->update(['contacted' => true]);
        return redirect()->back()->with('success', 'Donateur marqué comme contacté.');
    }

    /**
     * Met à jour l'ordre d'affichage des dons (drag & drop).
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|exists:donations,id',
        ]);

        foreach ($request->order as $index => $donationId) {
            Donation::where('id', $donationId)->update(['order' => $index + 1]);
        }

        return redirect()->back()->with('success', 'Ordre mis à jour.');
    }
}
