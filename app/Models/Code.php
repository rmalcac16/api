<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'codigos';

    protected $fillable = [
        'codigo', 'user_id', 'expires_at'
    ];

    protected $dates = [
        'created_at',
        'expires_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
