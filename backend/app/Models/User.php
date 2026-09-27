<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
/**
 * Modelo de Usuario.
 *
 * Representa la información persistida y las relaciones de Usuario dentro del ERP.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    protected $guarded = [];
    public function role(){return $this->belongsTo(\App\Models\Security\Role::class);}
    public function hasPermission(string $key):bool{return $this->role?->name==='Administrador'||$this->role?->permissions()->where('key',$key)->exists();}

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date'=>'date','hire_date'=>'date','last_login_at'=>'datetime','is_active'=>'boolean',
            'max_discount_percentage'=>'decimal:4','sales_commission_percentage'=>'decimal:4','collections_commission_percentage'=>'decimal:4','profit_commission_percentage'=>'decimal:4',
        ];
    }
}
