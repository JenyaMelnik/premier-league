<?php

namespace Drupal\football_league_simulator\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase {
  private $leagueService;
  private $schedule;

  public function __construct()
  {
    $this->leagueService = \Drupal::service('football_league_simulator.league_service');
    $this->schedule = $this->leagueService->generateSchedule();
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
