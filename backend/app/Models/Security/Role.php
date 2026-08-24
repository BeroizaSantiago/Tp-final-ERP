<?php
namespace App\Models\Security;use App\Models\User;use Illuminate\Database\Eloquent\Model;
/**
 * Modelo de Rol.
 *
 * Representa la información persistida y las relaciones de Rol dentro del ERP.
 */
class Role extends Model{protected $guarded=[];protected $casts=['is_active'=>'boolean','is_system'=>'boolean'];public function users(){return $this->hasMany(User::class);}public function permissions(){return $this->belongsToMany(Permission::class);}}
