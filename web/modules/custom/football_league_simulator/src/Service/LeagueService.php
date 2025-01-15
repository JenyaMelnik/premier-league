<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\football_league_simulator\Entity\Team;
use Drupal\football_league_simulator\Entity\FootballMatch;

class LeagueService {
  private $teams = [];
  private $matches = [];

  public function __construct() {
    $this->initializeTeams();
    $this->generateSchedule();
  }

  private function initializeTeams() {
    $this->teams = [
      new Team('Team A', 5),
      new Team('Team B', 4),
      new Team('Team C', 3),
      new Team('Team D', 2),
    ];
  }

  private function generateSchedule() {
    foreach ($this->teams as $team1) {
      foreach ($this->teams as $team2) {
        if ($team1 !== $team2) {
          $this->matches[] = new FootballMatch($team1, $team2);
        }
      }
    }
  }

  public function getMatches() {
    return $this->matches;
  }

  public function getTableWithPredictions() {
    $table = $this->getTable();
    $predictions = $this->calculatePredictions();
    foreach ($table as &$team) {
      $team['win_chance'] = $predictions[$team['name']]['chance'] ?? 0;
    }
    return $table;
  }

  public function getTable() {
    $table = [];
    foreach ($this->teams as $team) {
      $table[] = [
        'name' => $team->getName(),
        'points' => $team->getPoints(),
        'goals_for' => $team->getGoalsFor(),
        'goals_against' => $team->getGoalsAgainst(),
        'goal_difference' => $team->getGoalDifference(),
      ];
    }
    usort($table, fn($a, $b) => $b['points'] - $a['points']);
    return $table;
  }

  public function calculatePredictions() {
    $total_points = array_sum(array_map(fn($team) => $team->getPoints(), $this->teams));
    $predictions = [];
    foreach ($this->teams as $team) {
      $chance = $total_points ? ($team->getPoints() / $total_points) * 100 : 0;
      $predictions[$team->getName()] = ['chance' => round($chance, 2)];
    }
    return $predictions;
  }

  public function updateMatchResult(int $matchId, int $score1, int $score2) {
    $match = $this->matches[$matchId] ?? null;
    if ($match) {
      $match->updateResults($score1, $score2);
    }
  }
}
