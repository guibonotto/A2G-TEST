<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_exposes_aggregated_metrics(): void
    {
        $user = User::factory()->create();
        $status = TestCaseStatus::firstOrCreate(['name' => 'Aprovado'], ['color' => 'success']);
        $classification = Classification::firstOrCreate(['name' => 'Unitário']);

        TestCaseModel::create([
            'title' => 'Login válido',
            'classification_id' => $classification->id,
            'status_id' => $status->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('stats')
                ->has('statusBreakdown', 1)
                ->has('classificationBreakdown', 1)
                ->has('workload', 1)
                ->has('creationTrend')
            );
    }
}
