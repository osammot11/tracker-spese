<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
        'icon',
        'color',
    ];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class)->orderBy('name');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeExpenses($query)
    {
        return $query->whereIn('type', ['expense', 'both']);
    }

    public function scopeIncomes($query)
    {
        return $query->whereIn('type', ['income', 'both']);
    }
}
