<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionPlan extends Model
{
    use UsesUuid;

    const STATUS_INACTIVE = "INACTIVE";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_COMPLETED = "COMPLETED";

    /**
     * @var string
     */
    protected $table = "contribution_plans";

    /**
     * @var array
     */
    protected $guarded = [];

//    protected $dates = [
//        'payback_date',
//        'activation_date'
//    ];

//    protected $dateFormat = "Y-m-d";

//    /**
//     * Make the contribution_id into a custom readable format.
//     *
//     * @return string
//     */
//    public function getContribution_IdAttribute()
//    {
//        return "Contribution Plan - {$this->contribution_id}";
//    }

    /**
     * Get all of the contribution plan's transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    /**
     * The user who owns the loan.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the contribution status for a contribution plan.
     *
     * @return string
     */
    public function getContributionStatusAttribute()
    {
        if ($this->balance <= 0) {
            return self::STATUS_INACTIVE;
        } elseif (($this->balance > 0) && $this->paymentDateReached()) {
            return self::STATUS_COMPLETED;
        } elseif ($this->balance > 0) {
            return self::STATUS_ACTIVE;
        }
    }

    public function getInterestAttribute()
    {
        if ($this->status === self::STATUS_INACTIVE) {
            return 0;
        }

        if ($this->paymentDateReached()) {
            $noOfDays = ($this->activation_date->diffInDays($this->payment_date));
        } else {
            $noOfDays = ($this->activation_date->diffInDays(Carbon::today()));
        }

        return (($this->interest_rate / 36500) * $this->balance) * $noOfDays;
    }

    /**
     * Determine if the start date for a contribution plan has been reached.
     *
     * @return bool
     */
    public function startDateReached()
    {
        if (!isset($this->start_date)) {
            return false;
        }

        $today = Carbon::today();
        return $today->diffInDays($this->start_date, false) <= 0;
    }

    /**
     * Determine if the payment date for a contribution plan has been reached.
     *
     * @return bool
     */
    public function paymentDateReached()
    {
        if (!isset($this->payback_date)) {
            return false;
        }

        $today = Carbon::today();
        return $today->diffInDays($this->payback_date, false) <= 0;
    }

    /**
     * Determine if a contribution plan is completed
     * @return bool
     */
    public function getIsCompletedAttribute()
    {
        return $this->contributionStatus === self::STATUS_COMPLETED;
    }


    public function getContributionInterest()
    {
//        the contribution fixed amount
        $principal = $this->fixed_amount;
//        the contribution duration
        $duration = $this->duration / 12;
//        contribution interest rate
        $interest_rate = $this->interest_rate / 100;

        $contribution_accumulate_amount = $principal * (1 + ($interest_rate * $duration));
        return $contribution_accumulate_amount - $this->fixed_amount;

    }
}
