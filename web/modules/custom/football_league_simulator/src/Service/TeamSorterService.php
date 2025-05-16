<?php

namespace Drupal\football_league_simulator\Service;

class TeamSorterService {

  public static function sortTeams(array &$teams): void {
    usort($teams, function ($a, $b) {
      if ($a['points'] === $b['points']) {
        if ($a['goal_difference'] === $b['goal_difference']) {
          return strcmp($a['team_name'], $b['team_name']);
        }
        return $b['goal_difference'] <=> $a['goal_difference'];
      }
      return $b['points'] <=> $a['points'];
    });
  }

}
