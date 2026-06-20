<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что администратор проходит любые проверки Gate (через Gate::before).
     */
    public function test_admin_passes_all_gates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('is-admin'));
        $this->assertTrue($admin->can('is-manager'));
        $this->assertTrue($admin->can('anything-else'));
    }

    /**
     * Проверяет, что менеджер проходит только свой gate, а остальные — нет.
     */
    public function test_manager_passes_only_manager_gate(): void
    {
        $manager = User::factory()->manager()->create();

        $this->assertFalse($manager->can('is-admin'));
        $this->assertTrue($manager->can('is-manager'));
        $this->assertFalse($manager->can('anything-else'));
    }
}
