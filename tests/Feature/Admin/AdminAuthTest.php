<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Access\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Probe routes mirroring how real admin routes will be protected.
        Route::middleware(['api', 'auth:sanctum', 'admin', 'permission:roles.manage'])
            ->get('/api/v1/admin/_probe/roles', fn () => 'ok');
        Route::middleware(['api', 'auth:sanctum', 'admin', 'permission:products.view'])
            ->get('/api/v1/admin/_probe/products', fn () => 'ok');

        // Requests from the admin SPA origin are "stateful" (session + CSRF).
        $this->withHeaders(['Origin' => 'http://localhost:5174', 'Referer' => 'http://localhost:5174/']);
    }

    private function staff(Role $role, string $email = 'staff@example.com'): User
    {
        return User::factory()->create(['email' => $email, 'password' => self::PASSWORD])
            ->assignRole($role->value);
    }

    private function login(string $email = 'staff@example.com', string $password = self::PASSWORD)
    {
        return $this->postJson('/api/v1/admin/auth/login', compact('email', 'password'));
    }

    // ------------------------------------------------------------------ login

    public function test_admin_can_log_in_and_receives_roles_and_permissions(): void
    {
        $this->staff(Role::ProductManager);

        $this->login()
            ->assertOk()
            ->assertJsonPath('user.email', 'staff@example.com')
            ->assertJsonPath('roles.0', 'Product Manager')
            ->assertJsonPath('is_super_admin', false)
            ->assertJsonFragment(['products.create']);

        $this->assertAuthenticated('web');
        $this->getJson('/api/v1/admin/auth/me')->assertOk();
    }

    public function test_session_id_is_regenerated_on_login(): void
    {
        $this->staff(Role::Admin);

        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
        $before = session()->getId();

        $this->login()->assertOk();

        $this->assertNotSame($before, session()->getId());
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->staff(Role::Admin);

        $this->login(password: 'wrong-password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/v1/admin/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_customer_cannot_log_in_and_gets_the_same_generic_error(): void
    {
        User::factory()->create(['email' => 'customer@example.com', 'password' => self::PASSWORD]);

        $wrong = $this->login('staff@example.com', 'nope')->json('errors.email');
        $customer = $this->login('customer@example.com')
            ->assertUnprocessable()
            ->json('errors.email');

        $this->assertSame($wrong, $customer);
        $this->assertGuest('web');
    }

    // ----------------------------------------------------------- rate limiting

    public function test_login_is_rate_limited_per_email_and_ip(): void
    {
        $this->staff(Role::Admin);

        for ($i = 0; $i < 5; $i++) {
            $this->login(password: 'wrong')->assertUnprocessable();
        }

        // Locked out now, even with the right password.
        $this->login()->assertStatus(429)->assertJsonValidationErrors('email');
        $this->assertGuest('web');
    }

    public function test_rate_limit_key_ignores_email_case_and_whitespace(): void
    {
        $this->staff(Role::Admin);

        for ($i = 0; $i < 5; $i++) {
            $this->login(' STAFF@Example.com ', 'wrong');
        }

        $this->login('staff@example.com')->assertStatus(429);
    }

    public function test_rate_limit_does_not_affect_other_emails(): void
    {
        $this->staff(Role::Admin);
        $this->staff(Role::Admin, 'other@example.com');

        for ($i = 0; $i < 5; $i++) {
            $this->login(password: 'wrong');
        }

        $this->login('other@example.com')->assertOk();
    }

    public function test_successful_login_clears_the_attempt_counter(): void
    {
        $this->staff(Role::Admin);

        for ($i = 0; $i < 4; $i++) {
            $this->login(password: 'wrong');
        }
        $this->login()->assertOk();

        $this->assertSame(0, RateLimiter::attempts('staff@example.com|127.0.0.1'));
    }

    // ------------------------------------------------------------------ logout

    public function test_admin_can_log_out(): void
    {
        $this->staff(Role::Admin);
        $this->login()->assertOk();

        $this->postJson('/api/v1/admin/auth/logout')->assertNoContent();

        $this->assertGuest('web');
    }

    // ----------------------------------------------- unauthorized access (2)

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/admin/_probe/products')->assertUnauthorized();
    }

    public function test_authenticated_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/admin/auth/me')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/admin/_probe/products')->assertForbidden();
    }

    public function test_customer_holding_a_permission_directly_is_still_not_admin(): void
    {
        $customer = User::factory()->create();
        $customer->givePermissionTo('products.view');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/_probe/products')->assertForbidden();
    }

    public function test_staff_without_the_permission_is_forbidden(): void
    {
        $this->actingAs($this->staff(Role::ContentManager), 'sanctum')
            ->getJson('/api/v1/admin/_probe/roles')->assertForbidden();
    }

    public function test_staff_with_the_permission_is_allowed(): void
    {
        $this->actingAs($this->staff(Role::ProductManager), 'sanctum')
            ->getJson('/api/v1/admin/_probe/products')->assertOk();
    }

    // ------------------------------------------------------------ super admin

    public function test_super_admin_privileges_are_explicit(): void
    {
        $super = $this->staff(Role::SuperAdmin);
        $admin = $this->staff(Role::Admin, 'admin@example.com');

        $this->assertTrue($super->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());

        // Only the Super Admin passes roles.manage.
        $this->actingAs($super, 'sanctum')->getJson('/api/v1/admin/_probe/roles')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/_probe/roles')->assertForbidden();
    }

    public function test_super_admin_login_reports_flag(): void
    {
        $this->staff(Role::SuperAdmin);

        $this->login()->assertOk()->assertJsonPath('is_super_admin', true);
    }
}
