<?php

namespace Drupal\football_league_simulator\Controller;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase {
  private $leagueService;
  private $tournamentData;
  private $matchesData;
  private $allMatchesData;
  private $probabilities;
  private $allProbabilities;
  private $weeksLeft;

  public function __construct() {
    $this->leagueService = \Drupal::service('football_league_simulator.league_service');
    $this->leagueService->generateFirstWeek();
    $this->tournamentData = $this->leagueService->getTournamentData();
    $this->matchesData = $this->leagueService->getMatchesData();
    $this->probabilities = $this->leagueService->calculateWeekWinProbabilities();
    $this->allProbabilities = $this->leagueService->calculateTournamentWinProbabilities();
    $this->allMatchesData = $this->leagueService->getAllMatchesData();
    $this->weeksLeft = $this->leagueService->weeksLeft();
  }

  public function simulateWeek() {
    $this->leagueService->generateWeekMatches();

    \Drupal::service('session')->set('last_action', 'simulateWeek');

    return new JsonResponse(['success' => true]);
  }

  public function playAllMatches() {
    $this->leagueService->generateAllMatches();

    \Drupal::service('session')->set('last_action', 'playAllMatches');

    return new JsonResponse(['success' => true]);
  }

  public function playNewTournament() {
    $this->leagueService->generateNewTournament();

    return new JsonResponse(['success' => true]);
  }

  public function overview() {
    $session = \Drupal::service('session');
    $lastAction = $session->get('last_action', 'simulateWeek');

    $matches = ($lastAction === 'playAllMatches')
      ? $this->allMatchesData
      : $this->matchesData;

    $probabilities = ($lastAction === 'playAllMatches')
      ? $this->allProbabilities
      : $this->probabilities;

    return [
      '#theme' => 'football_league_overview',
      '#teams' => $this->tournamentData,
      '#matches' => $matches,
      '#probabilities' => $probabilities,
      '#weeksLeft' => $this->weeksLeft,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
