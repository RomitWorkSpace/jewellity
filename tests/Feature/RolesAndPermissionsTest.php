<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Access\Permission;
use App\Support\Access\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Route::middleware(['api', 'auth:sanctum', 'permission:products.create'])
            ->get('/api/_test/products', fn () => 'ok');
    }

    private function userWith(Role $role): User
    {
        return User::factory()->create()->assignRole($role->value);
    }

    public function test_all_roles_and_permissions_are_seeded(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role->value]);
        }
        $this->assertDatabaseCount('permissions', count(Permission::cases()));
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/_test/products')->assertUnauthorized();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->userWith(Role::CustomerSupport), 'sanctum')
            ->getJson('/api/_test/products')->assertForbidden();
    }

    public function test_role_with_permission_is_allowed(): void
    {
        $this->actingAs($this->userWith(Role::ProductManager), 'sanctum')
            ->getJson('/api/_test/products')->assertOk();
    }

    public function test_super_admin_bypasses_checks(): void
    {
        $user = $this->userWith(Role::SuperAdmin);
        $this->assertTrue($user->can('anything.at.all'));
    }

    public function test_admin_cannot_manage_roles(): void
    {
        $admin = $this->userWith(Role::Admin);
        $this->assertTrue($admin->can(Permission::ProductsDelete->value));
        $this->assertFalse($admin->can(Permission::RolesManage->value));
    }
}
