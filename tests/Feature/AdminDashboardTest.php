<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_dashboard_route_renders(): void
    {
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }
}