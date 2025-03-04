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
    $this->leagueService->generateFirstWeek();
    $this->tournamentData = $this->leagueService->getTournamentData();
    $this->lastWeek = $this->leagueService->getLastPlayedWeek();
    $this->matchesData = $this->leagueService->getMatchesData();
  }

  public function simulateWeek() {
    $this->leagueService->generateWeekMatches();
    $this->tournamentData = $this->leagueService->getTournamentData();
    $this->lastWeek = $this->leagueService->getLastPlayedWeek();
    $this->matchesData = $this->leagueService->getMatchesData();

    return new JsonResponse(['success' => true]);
  }

  public function playAllMatches() {
    $this->leagueService->generateAllMatches();
    $this->tournamentData = $this->leagueService->getTournamentData();
    $this->lastWeek = $this->leagueService->getLastPlayedWeek();
    $this->matchesData = $this->leagueService->getMatchesData();

    return new JsonResponse(['success' => true]);
  }

  public function overview() {
    return [
      '#theme' => 'football_league_overview',
      '#teams' => $this->tournamentData,
      '#lastWeek' => $this->lastWeek,
      '#matches' => $this->matchesData,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
