<?php

namespace App\Models\Concerns;

use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (! $model->company_id && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}