<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookAuthor extends Model
{
    /** @use HasFactory<\Database\Factories\BookAuthorFactory> */
    use HasFactory;

    public function books() : BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }

    public function authors() : BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }
}

