<?php

namespace Modules\AIAssistant\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Customer;
use App\Mailbox;

class CustomerContext extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ai_customer_context';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'mailbox_id',
        'context_summary',
        'common_issues',
        'communication_style',
        'preferences',
        'last_analyzed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'last_analyzed_at' => 'datetime',
    ];

    /**
     * Get the customer that this context belongs to.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the mailbox that this context belongs to.
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get common issues as an array.
     */
    public function getCommonIssuesArrayAttribute(): array
    {
        if (empty($this->common_issues)) {
            return [];
        }

        $decoded = json_decode($this->common_issues, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get preferences as an array.
     */
    public function getPreferencesArrayAttribute(): array
    {
        if (empty($this->preferences)) {
            return [];
        }

        $decoded = json_decode($this->preferences, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set common issues from array.
     */
    public function setCommonIssuesArrayAttribute(array $issues): void
    {
        $this->common_issues = json_encode($issues);
    }

    /**
     * Set preferences from array.
     */
    public function setPreferencesArrayAttribute(array $prefs): void
    {
        $this->preferences = json_encode($prefs);
    }

    /**
     * Check if the context is stale based on a given interval.
     */
    public function isStale(string $interval = 'weekly'): bool
    {
        if (!$this->last_analyzed_at) {
            return true;
        }

        $now = now();

        switch ($interval) {
            case 'daily':
                return $this->last_analyzed_at->diffInDays($now) >= 1;
            case 'weekly':
                return $this->last_analyzed_at->diffInWeeks($now) >= 1;
            case 'monthly':
                return $this->last_analyzed_at->diffInMonths($now) >= 1;
            default:
                return $this->last_analyzed_at->diffInWeeks($now) >= 1;
        }
    }

    /**
     * Scope to get context for a specific customer and mailbox.
     */
    public function scopeForCustomerMailbox($query, int $customerId, int $mailboxId)
    {
        return $query->where('customer_id', $customerId)
                     ->where('mailbox_id', $mailboxId);
    }

    /**
     * Scope to get stale contexts.
     */
    public function scopeStale($query, string $interval = 'weekly')
    {
        $threshold = now();

        switch ($interval) {
            case 'daily':
                $threshold->subDay();
                break;
            case 'weekly':
                $threshold->subWeek();
                break;
            case 'monthly':
                $threshold->subMonth();
                break;
        }

        return $query->where(function ($q) use ($threshold) {
            $q->whereNull('last_analyzed_at')
              ->orWhere('last_analyzed_at', '<', $threshold);
        });
    }

    /**
     * Get a formatted summary for display.
     */
    public function getFormattedSummary(): string
    {
        $parts = [];

        if (!empty($this->context_summary)) {
            $parts[] = $this->context_summary;
        }

        $issues = $this->common_issues_array;
        if (!empty($issues)) {
            $parts[] = "Common issues: " . implode(', ', $issues);
        }

        if (!empty($this->communication_style)) {
            $parts[] = "Communication style: " . $this->communication_style;
        }

        return implode("\n", $parts);
    }
}
