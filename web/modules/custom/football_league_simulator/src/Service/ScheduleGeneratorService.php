<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\football_league_simulator\Repository\TeamRepository;
use Drupal\football_league_simulator\Repository\TournamentRepository;

class ScheduleGeneratorService {

  protected TeamRepository $teamRepository;
  private TournamentRepository $tournamentRepository;

  public function __construct(TeamRepository $teamRepository, TournamentRepository $tournamentRepository) {
    $this->teamRepository = $teamRepository;
    $this->tournamentRepository = $tournamentRepository;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function generateWeek(int $week): mixed {
    $schedule = $this->generateSchedule();

    return $schedule[$week];
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function generateSchedule(): array {
    $teamIds = $this->teamRepository->getTeamIds();

    // Check if the number of teams is odd.
    if (count($teamIds) % 2 != 0) {
      $teamIds[] = null; // Add an ‘empty’ command if an odd number.
    }

    $numTeams = count($teamIds);
    $totalRounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;
    $schedule = [];
    $restingTeam = null;

    // Generation of the first round.
    for ($round = 0; $round < $totalRounds; $round++) {
      $roundMatches = [];
      for ($match = 0; $match < $matchesPerRound; $match++) {
        $home = ($round + $match) % ($numTeams - 1);
        $away = ($numTeams - 1 - $match + $round) % ($numTeams - 1);

        // The last team always stays in place while the others rotate.
        if ($match == 0) {
          $away = $numTeams - 1;
        }

        if ($teamIds[$home] === null) {
          $restingTeam = $teamIds[$away];
        } elseif ($teamIds[$away] === null) {
          $restingTeam = $teamIds[$home];
        } else {
          $roundMatches[] = [$teamIds[$home], $teamIds[$away]];
        }
      }
      $schedule[$round + 1] = [
        'matches' => $roundMatches,
        'resting_team' => $restingTeam,
      ];
    }

    // Generation of the second round (change hosts and guests).
    $secondRoundStart = count($schedule) + 1;

    foreach ($schedule as $roundData) {
      $returnRound = [];
      foreach ($roundData['matches'] as $match) {
        $returnRound[] = [$match[1], $match[0]]; // Change hosts and guests.
      }

      $schedule[$secondRoundStart++] = [
        'matches' => $returnRound,
        'resting_team' => $roundData['resting_team'],
      ];
    }

    return $schedule;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function weeksLeft(int $week = 0): int {
    $schedule = $this->generateSchedule();
    $totalWeeks = count($schedule);

    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();

    if ($week == 0) {
      return $totalWeeks - $lastPlayedWeek;
    } else {
      return $totalWeeks - $week;
    }
  }

}
