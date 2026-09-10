<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role',
        'avatar_path', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Matrice des droits d'accès — cahier des charges §9.
     * Clé = domaine de stock, valeur = rôles autorisés en LECTURE.
     */
    public const STOCK_READ = [
        'pharmacie' => ['admin', 'pharmacien', 'econome', 'medecin'],
        'consommable' => ['admin', 'pharmacien', 'econome', 'medecin', 'aide_soignant', 'reception'],
        'non_consommable' => ['admin', 'pharmacien', 'econome', 'medecin', 'aide_soignant', 'reception'],
        'cuisine' => ['admin', 'econome', 'cuisine'],
    ];

    /** Clé = domaine de stock, valeur = rôles autorisés en ÉCRITURE (entrées/sorties). */
    public const STOCK_WRITE = [
        'pharmacie' => ['admin', 'pharmacien'],
        'consommable' => ['admin', 'econome'],
        'non_consommable' => ['admin', 'econome'],
        'cuisine' => ['admin', 'econome', 'cuisine'],
    ];

    /** Rôles qui voient TOUS les rendez-vous (pas seulement les leurs). */
    public const APPOINTMENTS_VIEW_ALL = ['admin', 'reception', 'aide_soignant'];

    /** Rôles qui n'ont aucun accès au module rendez-vous. */
    public const APPOINTMENTS_NO_ACCESS = ['pharmacien', 'econome', 'cuisine'];

    public function canReadStockDomain(string $domain): bool
    {
        return in_array($this->role, self::STOCK_READ[$domain] ?? [], true);
    }

    public function canWriteStockDomain(string $domain): bool
    {
        return in_array($this->role, self::STOCK_WRITE[$domain] ?? [], true);
    }

    /** Domaines de stock que cet utilisateur peut consulter, au moins en lecture. */
    public function readableStockDomains(): array
    {
        return array_values(array_filter(
            array_keys(self::STOCK_READ),
            fn ($domain) => $this->canReadStockDomain($domain)
        ));
    }

    public function canAccessAppointments(): bool
    {
        return ! in_array($this->role, self::APPOINTMENTS_NO_ACCESS, true);
    }

    public function seesAllAppointments(): bool
    {
        return in_array($this->role, self::APPOINTMENTS_VIEW_ALL, true);
    }
}
