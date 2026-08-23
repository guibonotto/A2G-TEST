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
        $approvedStatus = TestCaseStatus::firstOrCreate(['name' => 'Aprovado'], ['color' => 'success']);
        $pendingStatus = TestCaseStatus::firstOrCreate(['name' => 'Pendente'], ['color' => 'warning']);
        $classification = Classification::firstOrCreate(['name' => 'Unitário']);

        TestCaseModel::create([
            'title' => 'Login válido',
            'classification_id' => $classification->id,
            'status_id' => $approvedStatus->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro válido',
            'classification_id' => $classification->id,
            'status_id' => $pendingStatus->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Logout válido',
            'classification_id' => $classification->id,
            'status_id' => $pendingStatus->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.total', 3)
                ->where('stats.unassigned', 2)
                ->where('stats.statusesInUse', 2)
                ->has('statusBreakdown', 2)
                ->where('statusBreakdown.0.name', 'Pendente')
                ->where('statusBreakdown.0.total', 2)
                ->where('statusBreakdown.1.name', 'Aprovado')
                ->where('statusBreakdown.1.total', 1)
                ->where('classificationBreakdown.0.total', 3)
                ->has('workload', 1)
                ->has('creationTrend')
            );
    }
}
