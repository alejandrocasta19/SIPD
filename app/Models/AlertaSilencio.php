<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaSilencio extends Model
{
    protected $table = 'sipd_alerta_silencios';

    protected $fillable = [
        'user_id',
        'tipo',
        'proceso_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
