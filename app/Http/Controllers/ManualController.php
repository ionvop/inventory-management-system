<?php

namespace App\Http\Controllers;

use App\Services\ManualService;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the in-app user manual.
 *
 * The manual is authored as Markdown files under `resources/docs/manual/` and
 * rendered server-side (see ManualService). It is available to every role, so
 * staff, supervisors and administrators all read the same guide.
 */
class ManualController extends Controller
{
    public function __construct(protected ManualService $manual) {}

    /**
     * Display the manual, optionally focused on a single section.
     */
    public function index(): Response
    {
        $requested = Request::query('section');
        $slug = $this->manual->resolve(is_string($requested) ? $requested : null);

        return Inertia::render('Manual', [
            'sections' => $this->manual->sections(),
            'selectedSlug' => $slug,
            'content' => $this->manual->html($slug),
        ]);
    }
}
