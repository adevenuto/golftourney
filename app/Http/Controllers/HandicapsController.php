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
        $index = $user->effectiveHandicapIndex();

        // The Index is portable — it comes from the player's rounds and needs no
        // league. Only the Course Handicap depends on a league's rating/slope/par.
        $you = [
            'index' => $this->handicaps->formatIndex($index),
            'index_value' => $index,
            'course_handicap' => $league ? $this->handicaps->courseHandicap($user, $league) : null,
            'league' => $league?->name,
            'holes' => $league?->holes,
            'course_rating' => $league ? (float) $league->course_rating : null,
            'slope_rating' => $league?->slope_rating,
            'par' => $league?->par,
        ];

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
