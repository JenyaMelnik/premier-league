<?php

namespace Drupal\football_league_simulator\Controller;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase {
  private $leagueService;
  private $tournamentData;
  private $lastWeek;
  private $matchesData;

  public function __construct() {
    $this->leagueService = \Drupal::service('football_league_simulator.league_service');
    $this->leagueService->generateWeekMatches();
    $this->tournamentData = $this->leagueService->getTournamentData();
    $this->lastWeek = $this->leagueService->getLastPlayedWeek();
    $this->matchesData = $this->leagueService->getMatchesData();
  }

  public function playWeek() {
//    $this->leagueService->generateWeekMatches();
//    $this->tournamentData = $this->leagueService->getTournamentData();
//    $this->lastWeek = $this->leagueService->getLastPlayedWeek();
//    $this->matchesData = $this->leagueService->getMatchesData();
  }

  public function overview() {
    return [
      '#theme' => 'football_league_overview',
      '#attached' => [
        'library' => ['football_league_simulator/football_league_js'],
      ],
      '#teams' => $this->tournamentData,
      '#lastWeek' => $this->lastWeek,
      '#matches' => $this->matchesData,
    ];
  }

}
