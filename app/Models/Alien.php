<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\Ability;

class Alien extends Model
{
    use HasFactory;
    // make guarded thingie
    protected $guarded = [];

    // make a belongsto relation to country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // create a many2many relationship with abilities
    public function abilities()
    {
        return $this->belongsToMany(Ability::class);
    }

    public function getApprovedBrowseAttribute()
    {
       if ($this->approved != 1) {
           return '<div style="background-color: red; border-radius: 5px; padding: 5px 10px; color: white; border: none;">Unapproved</div>';
       } else {
        return '<div style="background-color: green; border-radius: 5px; padding: 5px 10px; color: white; border: none;">OK</div>';
       }
    }

    public function scopeApproved($query)
        {
            return $query;
           // return $query->where('approved', 1);
        }


}
