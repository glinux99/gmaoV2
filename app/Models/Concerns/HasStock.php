<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
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
                    (SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = ' . $this->getTable() . '.id AND destination_region_id = ? AND type IN (?, ?))
                    -
                    (SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = ' . $this->getTable() . '.id AND source_region_id = ? AND type IN (?, ?))
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
        $tableName = $this->getTable();

        $stockSubQuery = DB::table('stock_movements as sm')
            ->selectRaw('COALESCE(SUM(CASE WHEN sm.destination_region_id = ? AND sm.type IN (?, ?) THEN sm.quantity ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN sm.source_region_id = ? AND sm.type IN (?, ?) THEN sm.quantity ELSE 0 END), 0)', [
                $regionId, 'entry', 'transfer',
                $regionId, 'exit', 'transfer'
            ])
            ->where('sm.movable_type', $movableType)
            ->whereColumn('sm.movable_id', "{$tableName}.id");

        return $query->addSelect([
            'stock_in_region' => $stockSubQuery
        ]);
    }

    protected function initializeHasStock()
    {
        $this->append('stock_in_region');
    }
}
