<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    /** @use HasFactory<\Database\Factories\GenreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        // 'description',
        // 'dewey_classification',
        // 'is_active',
    ];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
