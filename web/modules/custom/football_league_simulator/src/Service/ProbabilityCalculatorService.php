<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\football_league_simulator\Repository\TournamentRepository;

class ProbabilityCalculatorService {

  /** @var ScheduleGeneratorService  */
  private ScheduleGeneratorService $scheduleGeneratorService;
  /** @var TournamentRepository  */
  private TournamentRepository $tournamentRepository;

  /**
   * @param ScheduleGeneratorService $scheduleGeneratorService
   * @param TournamentRepository $tournamentRepository
   */
  public function __construct(ScheduleGeneratorService $scheduleGeneratorService, TournamentRepository $tournamentRepository) {
    $this->scheduleGeneratorService = $scheduleGeneratorService;
    $this->tournamentRepository = $tournamentRepository;
  }

  /**
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  function calculateWeekWinProbabilities(): array {
    $gamesLeft = $this->scheduleGeneratorService->weeksLeft();
    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();

    if ($lastPlayedWeek < 4) {
      return [];
    }

    $teams = $this->tournamentRepository->getTournamentData();
    if (!$teams) {
      return [];
    }

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

    $probabilities = [];

    if ($gamesLeft === 0) {
      usort($ratings, function ($a, $b) {
        if ($b['points'] !== $a['points']) {
          return $b['points'] - $a['points'];
        }
        return $b['goal_difference'] - $a['goal_difference'];
      });

      $topTeam = $ratings[0];
      $winners = array_filter($ratings, function ($team) use ($topTeam) {
        return $team['points'] === $topTeam['points'] && $team['goal_difference'] === $topTeam['goal_difference'];
      });

      foreach ($ratings as $team) {
        $probabilities[$lastPlayedWeek][] = [
          'team' => $team['team'],
          'probability' => isset($winners[$team['team']]) ? 100.0 : 0.0
        ];
      }
    } else {
      foreach ($ratings as $team) {
        $probability = $totalRating > 0 ? round($team['rating'] / $totalRating * 100, 2) : 0;
        $probabilities[$lastPlayedWeek][] = [
          'team' => $team['team'],
          'probability' => $probability
        ];
      }
    }

    return $probabilities;
  }

  /**
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  function calculateTournamentWinProbabilities(): array {
    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();
    $probabilitiesByWeek = [];

    if ($lastPlayedWeek < 4) {
      return [];
    }

    for ($week = 4; $week <= $lastPlayedWeek; $week++) {
      $gamesLeft = $this->scheduleGeneratorService->weeksLeft($week);
      $teams = $this->tournamentRepository->getTournamentData($week);

      if (!$teams) {
        continue;
      }

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
        usort($ratings, function ($a, $b) {
          if ($b['points'] !== $a['points']) {
            return $b['points'] - $a['points'];
          }
          return $b['goal_difference'] - $a['goal_difference'];
        });

        $topTeam = $ratings[0];
        $winners = array_filter($ratings, function ($team) use ($topTeam) {
          return $team['points'] === $topTeam['points'] && $team['goal_difference'] === $topTeam['goal_difference'];
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

      $probabilitiesByWeek[$week] = $weekProbabilities;
    }

    return $probabilitiesByWeek;
  }
}
