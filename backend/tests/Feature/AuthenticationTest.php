<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->boolean('is_active')->default(true); $table->boolean('is_system')->default(false); $table->timestamps(); });
        \DB::table('roles')->insert(['name'=>'Vendedor','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_a_user_is_registered_with_the_default_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.com',
        ]);

        $user = User::where('email', 'usuario@example.com')->firstOrFail();

        $response->assertRedirect('/demo/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('ERP123', $user->password));
    }

    public function test_a_user_can_log_in_and_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'ERP123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'ERP123',
        ])->assertRedirect('/demo/dashboard');

        $this->put('/profile/password', [
            'current_password' => 'ERP123',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NuevaClave123', $user->fresh()->password));
    }
}
