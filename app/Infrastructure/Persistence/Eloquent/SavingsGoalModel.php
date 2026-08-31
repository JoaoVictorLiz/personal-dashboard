<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent = linha da tabela. NAO e a entidade de dominio.
 * Nenhuma regra de negocio aqui - so o mapa objeto <-> tabela.
 * O EloquentSavingsGoalRepository traduz entre este Model e a
 * classe de dominio App\Domain\SavingsGoal\SavingsGoal.
 */
final class SavingsGoalModel extends Model
{
    protected $table = 'savings_goals';

    // PK e um UUID (string), nao auto-incremento.
    protected $keyType = 'string';

    public $incrementing = false;

    // O repositorio controla todas as escritas; sem mass-assignment magico.
    protected $guarded = [];

    protected $casts = [
        'target_amount_cents' => 'integer',
        'current_amount_cents' => 'integer',
        'target_date' => 'immutable_date',
    ];

    public function contributions(): HasMany
    {
        return $this->hasMany(ContributionModel::class, 'savings_goal_id');
    }
}
