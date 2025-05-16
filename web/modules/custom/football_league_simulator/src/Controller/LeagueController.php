<?php

namespace Drupal\football_league_simulator\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\football_league_simulator\Repository\MatchRepository;
use Drupal\football_league_simulator\Repository\TeamRepository;
use Drupal\football_league_simulator\Repository\TournamentRepository;
use Drupal\football_league_simulator\Service\MatchSimulatorService;
use Drupal\football_league_simulator\Service\ProbabilityCalculatorService;
use Drupal\football_league_simulator\Service\ScheduleGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class LeagueController extends ControllerBase {

  protected MatchSimulatorService $matchSimulatorService;
  protected ProbabilityCalculatorService $probabilityCalculatorService;
  protected ScheduleGeneratorService $scheduleGeneratorService;
  protected matchRepository $matchRepository;
  protected TeamRepository $teamRepository;
  protected TournamentRepository $tournamentRepository;

  private array $tournamentData;
  private array $weekMatchesData;
  private array $allMatchesData;
  private array $probabilities;
  private array $allProbabilities;
  private mixed $weeksLeft;

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function __construct(
    MatchSimulatorService $matchSimulatorService,
    ProbabilityCalculatorService $probabilityCalculatorService,
    ScheduleGeneratorService $scheduleGeneratorService,
    MatchRepository $matchRepository,
    TeamRepository $teamRepository,
    TournamentRepository $tournamentRepository
  ) {
    $this->matchSimulatorService = $matchSimulatorService;
    $this->probabilityCalculatorService = $probabilityCalculatorService;
    $this->scheduleGeneratorService = $scheduleGeneratorService;
    $this->matchRepository = $matchRepository;
    $this->teamRepository = $teamRepository;
    $this->tournamentRepository = $tournamentRepository;

    $this->matchSimulatorService->generateFirstWeek();
    $this->tournamentData = $this->tournamentRepository->getTournamentData();
    $this->weekMatchesData = $this->matchRepository->getMatchesData();
    $this->allMatchesData = $this->matchRepository->getMatchesData(-1);
    $this->probabilities = $this->probabilityCalculatorService->calculateWeekWinProbabilities();
    $this->allProbabilities = $this->probabilityCalculatorService->calculateTournamentWinProbabilities();
    $this->weeksLeft = $this->scheduleGeneratorService->weeksLeft();
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function create(ContainerInterface $container): LeagueController|static {
    return new static(
      $container->get('football_league_simulator.match_simulator_service'),
      $container->get('football_league_simulator.probability_calculator_service'),
      $container->get('football_league_simulator.schedule_generator_service'),
      $container->get('football_league_simulator.match_repository'),
      $container->get('football_league_simulator.team_repository'),
      $container->get('football_league_simulator.tournament_repository'),
    );
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function simulateWeek(): JsonResponse {
    $this->matchSimulatorService->generateWeekMatches();

    \Drupal::service('session')->set('last_action', 'simulateWeek');

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function playAllMatches(): JsonResponse {
    $this->matchSimulatorService->generateAllMatches();

    \Drupal::service('session')->set('last_action', 'playAllMatches');

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function playNewTournament(): JsonResponse {
    $this->tournamentRepository->generateNewTournament();

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function saveResult(Request $request): JsonResponse {
    $data = $request->request->all();
    $this->matchSimulatorService->changeResults($data);

    return new JsonResponse(['success' => TRUE, 'status' => 'ok', 'received' => $data]);
  }

  public function overview(): array {
    $session = \Drupal::service('session');
    $lastAction = $session->get('last_action', 'simulateWeek');

    $matches = ($lastAction === 'playAllMatches')
      ? $this->allMatchesData
      : $this->weekMatchesData;

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
