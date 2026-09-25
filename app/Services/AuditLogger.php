<?php

namespace App\Services;

use App\Http\Middleware\ResolveActiveProfile;
use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Records who changed what, and when (FR-8.1).
 *
 * Every create/edit/delete on catalog data, transactions, batches, and
 * profiles is attributed to the acting profile. Because the application is
 * passwordless (FR-1.1), this attribution is the primary accountability
 * mechanism, so high-impact actions must always be logged (NFR-2.2).
 */
class AuditLogger
{
    public function __construct(protected Request $request) {}

    /**
     * Record an audited action against a subject model.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        Model $subject,
        string $action,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'profile_id' => $this->actingProfile()?->id,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * The profile performing the current request, if one is selected.
     *
     * Profile management is reachable without an active profile (FR-1.4), so
     * this falls back to the session value when the middleware has not already
     * resolved the profile onto the request.
     */
    protected function actingProfile(): ?Profile
    {
        $profile = $this->request->attributes->get(ResolveActiveProfile::ATTRIBUTE);

        if ($profile instanceof Profile) {
            return $profile;
        }

        $profileId = Session::get(ResolveActiveProfile::SESSION_KEY);

        return is_int($profileId) || is_string($profileId)
            ? Profile::query()->findSole($profileId)
            : null;
    }
}
