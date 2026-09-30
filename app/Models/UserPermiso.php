<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermiso extends Model
{
    protected $fillable = [
        'user_id',
        'permiso',
        'expires_at',
        'granted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function otorgante()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function estaVigente(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function esTemporal(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
