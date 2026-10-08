<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveActiveProfile;
use App\Models\Profile;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the action-oriented dashboard.
 *
 * The dashboard is the department's landing screen (FR-1.2). It surfaces the
 * figures that need attention — stock to pull out, balances that block a
 * period close, overrides to review — alongside a small set of at-a-glance
 * totals and the latest movements. Alerts are scoped to the acting role so
 * each profile sees only the actions it can take.
 */
class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard) {}

    /**
     * Display the dashboard for the acting profile.
     */
    public function index(): Response
    {
        $profile = $this->actingProfile();
        $role = $profile instanceof Profile ? $profile->role : 'staff';

        return Inertia::render('Dashboard', $this->dashboard->build($role));
    }

    /**
     * The profile performing the current request, if one is selected.
     */
    protected function actingProfile(): ?Profile
    {
        $profile = request()->attributes->get(ResolveActiveProfile::ATTRIBUTE);

        return $profile instanceof Profile ? $profile : null;
    }
}
