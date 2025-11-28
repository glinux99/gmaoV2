<?php

namespace App\Enums;

enum StatutTache: string
{
    case PLANIFIEE = 'Planifiée';
    case EN_COURS = 'En cours';
    case TERMINEE = 'Terminée';
    case ANNULEE = 'Annulée';
    case EN_RETARD = 'En retard';
    case COMMENCEE_EN_RETARD = 'Commencée en retard';
}
