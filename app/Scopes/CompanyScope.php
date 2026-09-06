<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->company_id) {
            $builder->where($model->getTable() . '.company_id', Auth::user()->company_id);
        }
    }
}