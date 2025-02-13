<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\node\Entity\Node;

class LeagueService {
  public function generateWeek() {

  }

  public function generateSchedule(): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'team')
      ->accessCheck(TRUE)
      ->execute();

    $teams = Node::loadMultiple($query);
    $teamIds = array_keys($teams);

    // Check if the number of teams is odd.
    if (count($teamIds) % 2 != 0) {
      $teamIds[] = null; // Add an ‘empty’ command if an odd number.
    }

    $numTeams = count($teamIds);
    $totalRounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;
    $schedule = [];

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

        // Add a match if neither team is ‘null’.
        if ($teamIds[$home] !== null && $teamIds[$away] !== null) {
          $roundMatches[] = [$teamIds[$home], $teamIds[$away]];
        }
      }
      $schedule[$round + 1] = $roundMatches;
    }

    // Generation of the second round (change hosts and guests).
    $secondRoundStart = count($schedule) + 1;
    foreach ($schedule as $roundMatches) {
      $returnRound = [];
      foreach ($roundMatches as $match) {
        $returnRound[] = [$match[1], $match[0]]; // Change hosts and guests.
      }
      $schedule[$secondRoundStart++] = $returnRound;
    }

    return $schedule;
  }
}
