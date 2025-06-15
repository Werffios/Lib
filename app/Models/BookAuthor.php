<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookAuthor extends Model
{
    /** @use HasFactory<\Database\Factories\BookAuthorFactory> */
    use HasFactory;

    protected $fillable = [
        'book_id',
        'author_id',
    ];

    // La tabla pivote debe tener relaciones BelongsTo, no BelongsToMany
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}

