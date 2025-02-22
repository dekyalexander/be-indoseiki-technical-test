<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowsHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'borrows_id',
        'book_id',
        'user_id',
        'borrower_name',
        'borrow_date',
        'return_date',
    ];
}
