<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GosiSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:4',
        'verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Company override first, group-wide default (company_id null) second.
     */
    public static function valueFor(string $key, ?int $companyId = null, ?float $fallback = null): ?float
    {
        $setting = static::query()
            ->where('key', $key)
            ->where(fn ($query) => $companyId === null
                ? $query->whereNull('company_id')
                : $query->where('company_id', $companyId)->orWhereNull('company_id'))
            ->orderByRaw('company_id is null')
            ->first();

        return $setting !== null ? (float) $setting->value : $fallback;
    }
}
