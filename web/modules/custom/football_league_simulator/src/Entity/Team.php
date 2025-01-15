<?php

namespace Drupal\football_league_simulator\Entity;

class Team {
  private $name;
  private $points;
  private $goalsFor;
  private $goalsAgainst;

  public function __construct(string $name, int $points) {
    $this->name = $name;
    $this->points = $points;
    $this->goalsFor = 0;
    $this->goalsAgainst = 0;
  }

  public function updateStats(int $goalsFor, int $goalsAgainst) {
    $this->goalsFor += $goalsFor;
    $this->goalsAgainst += $goalsAgainst;

    if ($goalsFor > $goalsAgainst) {
      $this->points += 3;
    } elseif ($goalsFor == $goalsAgainst) {
      $this->points += 1;
    }
  }

  public function getName() {
    return $this->name;
  }

  public function getPoints() {
    return $this->points;
  }

  public function getGoalsFor() {
    return $this->goalsFor;
  }

  public function getGoalsAgainst() {
    return $this->goalsAgainst;
  }

  public function getGoalDifference() {
    return $this->goalsFor - $this->goalsAgainst;
  }
}
