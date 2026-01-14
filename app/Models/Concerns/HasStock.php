<?php

namespace App\Models\Concerns;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

trait HasStock
{
    /**
     * Calcule le stock actuel de l'article pour une région donnée (Version PHP).
     */
    public function getStockInRegion(int $regionId): int
    {
        $inflows = $this->stockMovements()
            ->where('destination_region_id', $regionId)
            ->whereIn('type', ['entry', 'transfer'])
            ->sum('quantity');

        $outflows = $this->stockMovements()
            ->where('source_region_id', $regionId)
            ->whereIn('type', ['exit', 'transfer'])
            ->sum('quantity');

        return (int) ($inflows - $outflows);
    }

    /**
     * Scope pour filtrer les articles ayant un stock > 0 dans une région.
     */
    public function scopeWhereHasStockInRegion(Builder $query, ?int $regionId): Builder
    {
        if (is_null($regionId)) return $query;

        return $query->where(function ($q) use ($regionId) {
            $tableName = $this->getTable();
            $movableType = $this->getMorphClass();

            $q->whereRaw(
                "(
                    COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = {$tableName}.id AND destination_region_id = ? AND type IN ('entry', 'transfer')), 0)
                    -
                    COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE movable_type = ? AND movable_id = {$tableName}.id AND source_region_id = ? AND type IN ('exit', 'transfer')), 0)
                ) > 0",
                [$movableType, $regionId, $movableType, $regionId]
            );
        });
    }

    /**
     * Relation polymorphique.
     */
    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }

    /**
     * CORRECTION DE L'ERREUR : Scope pour ajouter la colonne stock_in_region via SQL.
     */
    public function scopeWithStockInRegion(Builder $query, ?int $regionId): Builder
    {
        if (is_null($regionId)) {
            return $query->addSelect(DB::raw('0 as stock_in_region'));
        }

        $movableType = $this->getMorphClass();
        $tableName = $this->getTable();

        // On définit les sous-requêtes comme des instances de Builder (SANS exécuter ->toSql())
        $inflowsSql = StockMovement::selectRaw('COALESCE(SUM(quantity), 0)')
            ->where('movable_type', $movableType)
            ->whereColumn('movable_id', "{$tableName}.id")
            ->where('destination_region_id', $regionId)
            ->whereIn('type', ['entry', 'transfer']);

        $outflowsSql = StockMovement::selectRaw('COALESCE(SUM(quantity), 0)')
            ->where('movable_type', $movableType)
            ->whereColumn('movable_id', "{$tableName}.id")
            ->where('source_region_id', $regionId)
            ->whereIn('type', ['exit', 'transfer']);

        // On utilise selectSub avec des Closures pour que Laravel gère les bindings proprement
        return $query->addSelect([
            'stock_in_region' => function ($q) use ($inflowsSql, $outflowsSql) {
                $q->selectRaw("({$inflowsSql->toSql()}) - ({$outflowsSql->toSql()})")
                  ->mergeBindings($inflowsSql->getQuery())
                  ->mergeBindings($outflowsSql->getQuery());
            }
        ]);
    }

    /**
     * Accessor pour stock_in_region.
     */
    public function stockInRegion(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Si la valeur a été chargée via le scopeWithStockInRegion
                if (array_key_exists('stock_in_region', $attributes)) {
                    return (int) $attributes['stock_in_region'];
                }

                // Sinon calcul dynamique (Attention aux performances en liste)
                if (!isset($attributes['region_id'])) return 0;

                return $this->getStockInRegion((int) $attributes['region_id']);
            }
        );
    }

    public function scopeFilterByRegion(Builder $query, ?int $regionId): Builder
    {
        if (is_null($regionId)) return $query;
        return $query->where(function($q) use ($regionId) {
            $q->where('source_region_id', $regionId)
              ->orWhere('destination_region_id', $regionId);
        });
    }
}
