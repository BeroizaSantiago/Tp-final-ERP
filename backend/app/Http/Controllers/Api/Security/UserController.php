<?php
namespace App\Http\Controllers\Api\Security;use App\Http\Controllers\Controller;use App\Models\User;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
/**
 * Controlador de Usuario.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Usuario del ERP.
 */
class UserController extends Controller{
 public function index(Request$r){return User::with('role')->when($r->filled('search'),fn($q)=>$q->where(fn($x)=>$x->where('username','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')->orWhere('first_name','like','%'.$r->search.'%')->orWhere('last_name','like','%'.$r->search.'%')->orWhere('email','like','%'.$r->search.'%')))->orderBy('name')->paginate(20);}
 public function store(Request$r){$data=$this->validated($r);$data['name']=trim($data['first_name'].' '.$data['last_name']);return response()->json(User::create($data)->load('role'),201);}
 public function show(User$user){return $user->load('role');}
 public function update(Request$r,User$user){$data=$this->validated($r,$user);if(empty($data['password']))unset($data['password']);$data['name']=trim($data['first_name'].' '.$data['last_name']);$user->update($data);return $user->fresh()->load('role');}
 public function toggle(User$user){if(auth()->id()===$user->id&&$user->is_active)return response()->json(['message'=>'No podés desactivar tu propio usuario.'],422);$user->update(['is_active'=>!$user->is_active]);return $user->fresh()->load('role');}
 private function validated(Request$r,?User$user=null):array{return $r->validate(['username'=>['required','string','max:100',Rule::unique('users')->ignore($user)],'password'=>[$user?'nullable':'required','nullable','string','min:6','confirmed'],'first_name'=>['required','string','max:100'],'last_name'=>['required','string','max:100'],'birth_date'=>['nullable','date'],'hire_date'=>['nullable','date'],'phone'=>['nullable','string','max:50'],'email'=>['required','email','max:255',Rule::unique('users')->ignore($user)],'country'=>['nullable','string','max:100'],'province'=>['nullable','string','max:100'],'address'=>['nullable','string','max:255'],'neighborhood'=>['nullable','string','max:150'],'max_discount_percentage'=>['nullable','numeric','between:0,100'],'sales_commission_percentage'=>['nullable','numeric','between:0,100'],'collections_commission_percentage'=>['nullable','numeric','between:0,100'],'profit_commission_percentage'=>['nullable','numeric','between:0,100'],'role_id'=>['required','exists:roles,id'],'is_active'=>['nullable','boolean']]);}
}
