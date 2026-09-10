<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Une ligne = une clinique cliente. On s'appuie directement sur la table
 * "tenants" fournie par stancl/tenancy plutôt qu'une table "clinics" maison.
 * Tous les champs personnalisés (name, color_primary, modules_enabled...)
 * sont stockés automatiquement dans la colonne JSON "data" de cette table.
 */
class Clinic extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function hasModule(string $module): bool
    {
        return (bool) (($this->modules_enabled ?? [])[$module] ?? false);
    }
}
