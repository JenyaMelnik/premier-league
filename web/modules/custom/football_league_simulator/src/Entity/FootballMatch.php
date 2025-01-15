<?php

namespace Drupal\football_league_simulator\Entity;

class FootballMatch {
  private $team1;
  private $team2;
  private $score1;
  private $score2;

  public function __construct(Team $team1, Team $team2) {
    $this->team1 = $team1;
    $this->team2 = $team2;
    $this->score1 = 0;
    $this->score2 = 0;
  }

  public function updateResults(int $score1, int $score2) {
    $this->score1 = $score1;
    $this->score2 = $score2;

    // Update teams' stats
    $this->team1->updateStats($score1, $score2);
    $this->team2->updateStats($score2, $score1);
  }

  public function getTeam1() {
    return $this->team1;
  }

  public function getTeam2() {
    return $this->team2;
  }

  public function getScore1() {
    return $this->score1;
  }

  public function getScore2() {
    return $this->score2;
  }
}
