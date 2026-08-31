<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContributionModel extends Model
{
    protected $table = 'contributions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'amount_cents' => 'integer',
        'date' => 'immutable_date',
    ];

    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoalModel::class, 'savings_goal_id');
    }
}
