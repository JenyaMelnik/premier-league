<?php

namespace Drupal\football_league_simulator\Controller;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase {
  private $leagueService;
  private $schedule;
  private $match;

  public function __construct() {
    $this->leagueService = \Drupal::service('football_league_simulator.league_service');
    $this->schedule = $this->leagueService->generateSchedule();
    $this->match = $this->leagueService->generateMatch();
  }

  public function overview()
  {
    return [
      '#theme' => 'football_league_overview',
      '#attached' => [
        'library' => ['football_league_simulator/football_league_js'],
      ],
    ];
  }

}
