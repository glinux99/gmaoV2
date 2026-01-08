<?php

namespace App\Models\Concerns;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

trait HasStock
{
    /**
     * Calcule le stock actuel de l'article pour une région donnée.
     *
     * @param int $regionId L'ID de la région.
     * @return int
     */
    public function getStockInRegion(int $regionId): int
    {
        // Somme des quantités entrantes (type 'entry' ou 'transfer' vers cette région)
        $inflows = $this->stockMovements()
            ->where('destination_region_id', $regionId)
            ->whereIn('type', ['entry', 'transfer'])
            ->sum('quantity');

        // Somme des quantités sortantes (type 'exit' ou 'transfer' depuis cette région)
        $outflows = $this->stockMovements()
            ->where('source_region_id', $regionId)
            ->whereIn('type', ['exit', 'transfer'])
            ->sum('quantity');

        return $inflows - $outflows;
    }

    /**
     * Scope pour ne récupérer que les articles ayant du stock dans une région spécifique.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $regionId L'ID de la région. Si null, le scope ne fait rien.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereHasStockInRegion(Builder $query, ?int $regionId): Builder
    {
        if (is_null($regionId)) {
            // Si aucune région n'est spécifiée, on ne filtre pas.
            // On pourrait aussi choisir de ne rien retourner : return $query->whereRaw('1 = 0');
            return $query;
        }

        // On utilise une sous-requête pour calculer le stock net et on filtre
        // les articles pour lesquels ce stock est supérieur à zéro.
        return $query->where(function ($q) use ($regionId) {
            $q->whereRaw(
                '(
                    COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = ' . $this->getTable() . '.id AND destination_region_id = ? AND type IN (?, ?)), 0)
                    -
                    COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = ' . $this->getTable() . '.id AND source_region_id = ? AND type IN (?, ?)), 0)
                ) > 0',
                [
                    // Bindings pour la première sous-requête (entrées)
                    self::class,
                    $regionId,
                    'entry',
                    'transfer',
                    // Bindings pour la deuxième sous-requête (sorties)
                    self::class,
                    $regionId,
                    'exit',
                    'transfer'
                ]
            );
        });
    }

    /**
     * Relation polymorphique vers les mouvements de stock.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function stockMovements()
    {
        return $this->morphMany(\App\Models\StockMovement::class, 'movable');
    }

    /**
     * Scope pour ajouter le stock calculé pour une région donnée.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $regionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStockInRegion(Builder $query, ?int $regionId): Builder
    {
         if (is_null($regionId)) {
             return $query->addSelect(DB::raw('NULL as stock_in_region'));
         }

         $movableType = $this->getMorphClass();

         // Sous-requête optimisée pour calculer le stock net
         $stockSubQuery = StockMovement::query()
             ->select(DB::raw('SUM(CASE
                 WHEN type = \'entry\' THEN quantity
                 WHEN type = \'transfer\' AND destination_region_id = ? THEN quantity
                 WHEN type = \'exit\' THEN -quantity
                 WHEN type = \'transfer\' AND source_region_id = ? THEN -quantity
                 ELSE 0
             END)'))
             ->where('movable_type', $movableType)
             ->whereColumn('movable_id', $this->getTable() . '.id')
             ->where(function ($q) use ($regionId) {
                 $q->where('destination_region_id', $regionId)
                   ->orWhere('source_region_id', $regionId);
             })
             ->groupBy('movable_id');

         // Application des bindings pour la sous-requête
         $stockSubQuery->addBinding([$regionId, $regionId], 'select');

        return $query->addSelect([
            'stock_in_region' => $stockSubQuery
        ]);
    }

    protected function initializeHasStock()
    {
        $this->append('stock_in_region');
    }

    /**
     * Scope pour filtrer les mouvements par région (source ou destination).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $regionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByRegion(Builder $query, ?int $regionId): Builder
    {
        if (is_null($regionId)) return $query;
        return $query->where('source_region_id', $regionId)->orWhere('destination_region_id', $regionId);
    }
       public function stockInRegion(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!isset($attributes['region_id'])) {
                    return null;
                }

                return StockMovement::where('movable_type', self::class)
                    ->where('movable_id', $attributes['id'])
                    ->where('destination_region_id', $attributes['region_id'])
                    ->sum('quantity');
            }
        );
    }
}
