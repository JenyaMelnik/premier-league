<?php

namespace Drupal\football_league_simulator\Service;

/**
 * Service for sorting teams based on their performance metrics.
 */
class TeamSorterService {

  /**
   * Sorts teams based on points, goal difference, and team name.
   *
   * @param array $teams
   *   Array of teams with their performance metrics.
   */
  public function sortTeams(array &$teams): void {
    usort($teams, [$this, 'compareTeams']);
  }

  /**
   * Compares two teams based on their performance metrics.
   *
   * @param array $a
   *   First team to compare.
   * @param array $b
   *   Second team to compare.
   *
   * @return int
   *   Comparison result (-1, 0, or 1).
   */
  protected function compareTeams(array $a, array $b): int {
    // First compare by points
    if ($a['points'] !== $b['points']) {
      return $b['points'] <=> $a['points'];
    }

    // If points are equal, compare by goal difference
    if ($a['goal_difference'] !== $b['goal_difference']) {
      return $b['goal_difference'] <=> $a['goal_difference'];
    }

    // If goal difference is equal, sort alphabetically by team name
    return strcmp($a['team_name'], $b['team_name']);
  }

}
