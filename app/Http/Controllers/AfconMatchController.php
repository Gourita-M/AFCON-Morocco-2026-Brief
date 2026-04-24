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
            $fallbackMatch = $this->findSampleMatch($matchId);

            if ($fallbackMatch) {
                return response()->json(['data' => $fallbackMatch]);
            }

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
            $fallbackMatch = $this->findSampleMatch($matchId);

            if ($fallbackMatch) {
                return response()->json(['data' => $fallbackMatch]);
            }

            return response()->json([
                'message' => 'Unable to fetch match details from football provider.',
            ], 503);
        }

        if ($fixtureResponse->failed() || empty($fixtureResponse->json('response'))) {
            $fallbackMatch = $this->findSampleMatch($matchId);

            if ($fallbackMatch) {
                return response()->json(['data' => $fallbackMatch]);
            }

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

    private function sampleMatches(): array
    {
        return [
            [
                'id' => 1,
                'stage' => 'Quarter-final',
                'date' => '2026-02-06',
                'time' => '17:00',
                'stadium' => 'Stade Moulay Abdellah, Rabat',
                'homeTeam' => 'Morocco',
                'awayTeam' => 'Nigeria',
                'homeScore' => 2,
                'awayScore' => 1,
                'manOfTheMatch' => 'Achraf Hakimi',
                'statistics' => [
                    ['label' => 'Possession', 'home' => '56%', 'away' => '44%'],
                    ['label' => 'Shots', 'home' => 13, 'away' => 9],
                    ['label' => 'Shots on target', 'home' => 6, 'away' => 4],
                    ['label' => 'Corners', 'home' => 7, 'away' => 3],
                ],
                'scorers' => [
                    ['team' => 'Morocco', 'player' => 'Youssef En-Nesyri', 'minute' => "18'"],
                    ['team' => 'Nigeria', 'player' => 'Victor Osimhen', 'minute' => "39'"],
                    ['team' => 'Morocco', 'player' => 'Achraf Hakimi', 'minute' => "74'"],
                ],
                'cards' => [
                    ['team' => 'Morocco', 'player' => 'Sofyan Amrabat', 'type' => 'Yellow', 'minute' => "45+1'"],
                    ['team' => 'Nigeria', 'player' => 'Wilfred Ndidi', 'type' => 'Yellow', 'minute' => "63'"],
                ],
            ],
            [
                'id' => 2,
                'stage' => 'Quarter-final',
                'date' => '2026-02-06',
                'time' => '20:00',
                'stadium' => 'Grand Stade de Marrakech, Marrakech',
                'homeTeam' => 'Senegal',
                'awayTeam' => 'Algeria',
                'homeScore' => 1,
                'awayScore' => 1,
                'manOfTheMatch' => null,
                'statistics' => [
                    ['label' => 'Possession', 'home' => '48%', 'away' => '52%'],
                    ['label' => 'Shots', 'home' => 11, 'away' => 12],
                    ['label' => 'Shots on target', 'home' => 5, 'away' => 3],
                    ['label' => 'Corners', 'home' => 4, 'away' => 6],
                ],
                'scorers' => [
                    ['team' => 'Senegal', 'player' => 'Sadio Mane', 'minute' => "57'"],
                    ['team' => 'Algeria', 'player' => 'Riyad Mahrez', 'minute' => "81'"],
                ],
                'cards' => [
                    ['team' => 'Senegal', 'player' => 'Kalidou Koulibaly', 'type' => 'Yellow', 'minute' => "28'"],
                    ['team' => 'Algeria', 'player' => 'Ismael Bennacer', 'type' => 'Yellow', 'minute' => "65'"],
                    ['team' => 'Algeria', 'player' => 'Aissa Mandi', 'type' => 'Red', 'minute' => "90+2'"],
                ],
            ],
            [
                'id' => 3,
                'stage' => 'Quarter-final',
                'date' => '2026-02-07',
                'time' => '17:00',
                'stadium' => 'Stade Ibn Batouta, Tangier',
                'homeTeam' => 'Egypt',
                'awayTeam' => 'Cameroon',
                'homeScore' => 0,
                'awayScore' => 2,
                'manOfTheMatch' => 'Andre-Frank Zambo Anguissa',
                'statistics' => [
                    ['label' => 'Possession', 'home' => '50%', 'away' => '50%'],
                    ['label' => 'Shots', 'home' => 10, 'away' => 14],
                    ['label' => 'Shots on target', 'home' => 2, 'away' => 7],
                    ['label' => 'Corners', 'home' => 5, 'away' => 5],
                ],
                'scorers' => [
                    ['team' => 'Cameroon', 'player' => 'Karl Toko Ekambi', 'minute' => "33'"],
                    ['team' => 'Cameroon', 'player' => 'Vincent Aboubakar', 'minute' => "68'"],
                ],
                'cards' => [
                    ['team' => 'Egypt', 'player' => 'Hamdi Fathi', 'type' => 'Yellow', 'minute' => "22'"],
                    ['team' => 'Cameroon', 'player' => 'Nicolas Nkoulou', 'type' => 'Yellow', 'minute' => "49'"],
                ],
            ],
            [
                'id' => 4,
                'stage' => 'Quarter-final',
                'date' => '2026-02-07',
                'time' => '20:00',
                'stadium' => 'Stade d\'Agadir, Agadir',
                'homeTeam' => 'Cote d\'Ivoire',
                'awayTeam' => 'Tunisia',
                'homeScore' => 3,
                'awayScore' => 2,
                'manOfTheMatch' => 'Sebastien Haller',
                'statistics' => [
                    ['label' => 'Possession', 'home' => '53%', 'away' => '47%'],
                    ['label' => 'Shots', 'home' => 16, 'away' => 11],
                    ['label' => 'Shots on target', 'home' => 8, 'away' => 4],
                    ['label' => 'Corners', 'home' => 6, 'away' => 4],
                ],
                'scorers' => [
                    ['team' => 'Cote d\'Ivoire', 'player' => 'Sebastien Haller', 'minute' => "12'"],
                    ['team' => 'Tunisia', 'player' => 'Youssef Msakni', 'minute' => "31'"],
                    ['team' => 'Cote d\'Ivoire', 'player' => 'Nicolas Pepe', 'minute' => "54'"],
                    ['team' => 'Tunisia', 'player' => 'Anis Ben Slimane', 'minute' => "71'"],
                    ['team' => 'Cote d\'Ivoire', 'player' => 'Franck Kessie', 'minute' => "85'"],
                ],
                'cards' => [
                    ['team' => 'Tunisia', 'player' => 'Ellyes Skhiri', 'type' => 'Yellow', 'minute' => "40'"],
                    ['team' => 'Cote d\'Ivoire', 'player' => 'Serge Aurier', 'type' => 'Yellow', 'minute' => "77'"],
                ],
            ],
        ];
    }

    private function findSampleMatch(int $matchId): ?array
    {
        return collect($this->sampleMatches())
            ->first(fn (array $match) => $match['id'] === $matchId);
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
