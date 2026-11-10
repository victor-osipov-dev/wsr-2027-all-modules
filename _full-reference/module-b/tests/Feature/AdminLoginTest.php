<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Проверка доступности экрана входа в админ-панель.
 */
class AdminLoginTest extends TestCase
{
    public function test_login_page_is_available(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Вход в админ-панель');
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $response = $this->get('/admin/recipes');

        $response->assertRedirect('/admin/login');
    }
}
