<?php

namespace Drupal\football_league_simulator\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase
{
  private $leagueService;

  public function __construct()
  {
    $this->leagueService = \Drupal::service('football_league_simulator.league_service');
  }

  public function overview()
  {
    return [
      '#theme' => 'football_league_overview',
      '#matches' => $this->leagueService->getMatches(),
      '#data' => $this->leagueService->getTableWithPredictions(),
      '#attached' => [
        'library' => ['football_league_simulator/football_league_js'],
      ],
    ];
  }

  public function saveMatchResults(Request $request)
  {
    $data = $request->get('matches');
    if (empty($data)) {
      return new JsonResponse(['status' => 'error', 'message' => 'No data provided'], 400);
    }

    foreach ($data as $matchId => $scores) {
      $this->leagueService->updateMatchResult($matchId, (int)$scores['score1'], (int)$scores['score2']);
    }

    $table = $this->leagueService->getTableWithPredictions();

    return new JsonResponse([
      'status' => 'success',
      'table' => $table,
    ]);
  }
}
