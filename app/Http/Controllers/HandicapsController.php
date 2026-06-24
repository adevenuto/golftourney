<?php

namespace App\Http\Controllers;

use App\Services\HandicapService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HandicapsController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Explain how WHS handicaps work, anchored by the viewer's own numbers.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $league = $user->currentLeague;

        $you = $league ? [
            'index' => $this->handicaps->formatIndex($user->effectiveHandicapIndex()),
            'index_value' => $user->effectiveHandicapIndex(),
            'course_handicap' => $this->handicaps->courseHandicap($user, $league),
            'league' => $league->name,
            'holes' => $league->holes,
            'course_rating' => (float) $league->course_rating,
            'slope_rating' => $league->slope_rating,
            'par' => $league->par,
        ] : null;

        return Inertia::render('Handicaps', [
            'you' => $you,
            'constants' => [
                'recentWindow' => HandicapService::RECENT_WINDOW,
                'minimumRounds' => HandicapService::MINIMUM_ROUNDS,
                'standardSlope' => HandicapService::STANDARD_SLOPE,
            ],
        ]);
    }
}
