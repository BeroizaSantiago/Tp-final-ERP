<?php
namespace App\Http\Controllers\Api\Security;use App\Http\Controllers\Controller;use App\Models\Security\Role;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
/**
 * Controlador de Rol.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Rol del ERP.
 */
class RoleController extends Controller{public function index(){return Role::withCount('users')->orderBy('name')->get();}public function store(Request$r){return response()->json(Role::create($r->validate(['name'=>['required','string','max:100','unique:roles,name'],'is_active'=>['nullable','boolean']])),201);}public function update(Request$r,Role$role){$role->update($r->validate(['name'=>['required','string','max:100',Rule::unique('roles')->ignore($role)],'is_active'=>['required','boolean']]));return $role->fresh()->loadCount('users');}public function destroy(Role$role){if($role->name==='Administrador')return response()->json(['message'=>'El rol Administrador no puede eliminarse.'],422);if($role->users()->exists())return response()->json(['message'=>'Primero debés reasignar los usuarios que utilizan este rol.'],422);$role->delete();return response()->json(['message'=>'Rol eliminado correctamente.']);}}
