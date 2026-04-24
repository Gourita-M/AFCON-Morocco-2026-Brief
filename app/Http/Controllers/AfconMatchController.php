<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AfconMatchController extends Controller
{
    public function quarterFinals(): JsonResponse
    {
        $request = $this->footballRequest();
        $leagueId = config('services.football.league_id');
        $season = config('services.football.season');
        $round = config('services.football.round');

        if (!$request) {
            return response()->json([
                'data' => $this->sampleMatches(),
            ]);
        }

        $query = [
            'league' => $leagueId,
            'season' => $season,
        ];

        if (!empty($round)) {
            $query['round'] = $round;
        }

        try {
            $response = $request->get('/fixtures', $query);
        } catch (\Throwable $exception) {
            return response()->json([
                'data' => $this->sampleMatches(),
            ]);
        }

        if ($response->failed()) {
            return response()->json([
                'data' => $this->sampleMatches(),
            ]);
        }

        $matches = collect($response->json('response', []));

        if (!empty($round)) {
            $matches = $matches->filter(function (array $match) use ($round) {
                return str_contains(
                    strtolower((string) Arr::get($match, 'league.round', '')),
                    strtolower($round)
                );
            });
        }

        $matches = $matches
            ->take(4)
            ->map(fn (array $match) => $this->transformMatchSummary($match))
            ->values();

        return response()->json(['data' => $matches]);
    }

    public function show(int $matchId): JsonResponse
    {
        $request = $this->footballRequest();
        $leagueId = config('services.football.league_id');
        $season = config('services.football.season');

        if (!$request) {
            return response()->json([
                'message' => 'FOOTBALL_API_KEY is missing. Configure it in your .env file.',
            ], 500);
        }

        try {
            $fixtureResponse = $request->get('/fixtures', [
                'league' => $leagueId,
                'season' => $season,
                'id' => $matchId,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Unable to fetch match details from football provider.',
                'error' => $exception->getMessage(),
            ], 503);
        }

        if ($fixtureResponse->failed() || empty($fixtureResponse->json('response'))) {
            return response()->json(['message' => 'Match not found.'], 404);
        }

        $fixture = $fixtureResponse->json('response.0', []);

        return response()->json([
            'data' => $this->transformMatchDetails($fixture),
        ]);
    }

    private function transformMatchSummary(array $match): array
    {
        $venueName = Arr::get($match, 'fixture.venue.name');
        $venueCity = Arr::get($match, 'fixture.venue.city');

        $stadium = collect([$venueName, $venueCity])
            ->filter()
            ->implode(', ');

        return [
            'id' => Arr::get($match, 'fixture.id'),
            'stage' => Arr::get($match, 'league.round', 'Quarter-final'),
            'date' => substr((string) Arr::get($match, 'fixture.date', ''), 0, 10),
            'time' => substr((string) Arr::get($match, 'fixture.date', ''), 11, 5),
            'stadium' => $stadium ?: 'Not available',
            'homeTeam' => Arr::get($match, 'teams.home.name', 'Home'),
            'awayTeam' => Arr::get($match, 'teams.away.name', 'Away'),
            'homeScore' => Arr::get($match, 'goals.home', 0) ?? 0,
            'awayScore' => Arr::get($match, 'goals.away', 0) ?? 0,
            'manOfTheMatch' => Arr::get($match, 'player.name'),
        ];
    }

    private function transformMatchDetails(array $match): array
    {
        $summary = $this->transformMatchSummary($match);

        return array_merge($summary, [
            'statistics' => [],
            'scorers' => [],
            'cards' => [],
        ]);
    }

    private function footballRequest()
    {
        $baseUrl = config('services.football.base_url');
        $apiKey = config('services.football.api_key');
        $tokenHeader = config('services.football.token_header', 'fb-api-token');

        if (!$apiKey) {
            return null;
        }

        return Http::baseUrl($baseUrl)
            ->withHeaders([
                $tokenHeader => $apiKey,
            ])
            ->withoutVerifying()
            ->retry(2, 100)
            ->timeout(10);
    }
}
