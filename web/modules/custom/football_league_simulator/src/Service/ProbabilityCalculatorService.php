<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\football_league_simulator\Repository\TournamentRepository;

class ProbabilityCalculatorService {

  private ScheduleGeneratorService $scheduleGeneratorService;
  private TournamentRepository $tournamentRepository;

  public function __construct(ScheduleGeneratorService $scheduleGeneratorService, TournamentRepository $tournamentRepository) {
    $this->scheduleGeneratorService = $scheduleGeneratorService;
    $this->tournamentRepository = $tournamentRepository;
  }

  private function calculateProbabilities(array $teams, int $gamesLeft): array {
    $maxPoints = max(array_column($teams, 'points'));
    $totalRating = 0;
    $ratings = [];

    foreach ($teams as $team) {
      $baseRating = $team['points'] * 100;
      $goalFactor = $team['goal_difference'] * 5;
      $rating = $baseRating + $goalFactor;

      if (($maxPoints - $team['points']) > (3 * $gamesLeft)) {
        $rating = 0;
      }

      $ratings[] = [
        'team' => $team['team_name'],
        'points' => $team['points'],
        'goal_difference' => $team['goal_difference'],
        'rating' => $rating
      ];
      $totalRating += $rating;
    }

    $weekProbabilities = [];

    if ($gamesLeft === 0) {
      $topTeam = $ratings[0];
      $winners = array_filter($ratings, function ($team) use ($topTeam) {
        return $team['points'] === $topTeam['points']
          && $team['goal_difference'] === $topTeam['goal_difference'];
      });

      foreach ($ratings as $team) {
        $weekProbabilities[] = [
          'team' => $team['team'],
          'probability' => in_array($team, $winners) ? 100.0 : 0.0
        ];
      }
    } else {
      foreach ($ratings as $team) {
        $probability = $totalRating > 0 ? round($team['rating'] / $totalRating * 100, 2) : 0;
        $weekProbabilities[] = [
          'team' => $team['team'],
          'probability' => $probability
        ];
      }
    }

    return $weekProbabilities;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function calculateWinProbabilitiesForWeeks(?int $specificWeek = null): array {
    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();

    if ($lastPlayedWeek < 4) {
      return [];
    }

    $weeks = $specificWeek !== null ? [$specificWeek] : range(4, $lastPlayedWeek);
    $probabilities = [];

    foreach ($weeks as $week) {
      $gamesLeft = $this->scheduleGeneratorService->weeksLeft($week);
      $teams = $this->tournamentRepository->getTournamentData($week);

      if (!$teams) {
        continue;
      }

      $probabilities[$week] = $this->calculateProbabilities($teams, $gamesLeft);
    }

    return $probabilities;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function calculateWeekWinProbabilities(): array {
    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();
    return $this->calculateWinProbabilitiesForWeeks($lastPlayedWeek);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function calculateTournamentWinProbabilities(): array {
    return $this->calculateWinProbabilitiesForWeeks();
  }

}
