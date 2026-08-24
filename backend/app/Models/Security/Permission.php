<?php
namespace App\Models\Security;use Illuminate\Database\Eloquent\Model;
/**
 * Modelo de Permission.
 *
 * Representa la información persistida y las relaciones de Permission dentro del ERP.
 */
class Permission extends Model{protected $guarded=[];public function roles(){return $this->belongsToMany(Role::class);}}
