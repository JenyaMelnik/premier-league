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
  /** @var MatchSimulatorService  */
  protected MatchSimulatorService $matchSimulatorService;
  /** @var ProbabilityCalculatorService  */
  protected ProbabilityCalculatorService $probabilityCalculatorService;
  /** @var ScheduleGeneratorService  */
  protected ScheduleGeneratorService $scheduleGeneratorService;
  /** @var MatchRepository  */
  protected matchRepository $matchRepository;
  /** @var TeamRepository  */
  protected TeamRepository $teamRepository;
  /** @var TournamentRepository  */
  protected TournamentRepository $tournamentRepository;

  /** @var array  */
  private array $tournamentData;
  /** @var array  */
  private array $matchesData;
  /** @var array  */
  private array $allMatchesData;
  /** @var array  */
  private array $probabilities;
  /** @var array  */
  private array $allProbabilities;
  /** @var int|mixed  */
  private mixed $weeksLeft;

  /**
   * @param MatchSimulatorService $matchSimulatorService
   * @param ProbabilityCalculatorService $probabilityCalculatorService
   * @param ScheduleGeneratorService $scheduleGeneratorService
   * @param MatchRepository $matchRepository
   * @param TeamRepository $teamRepository
   * @param TournamentRepository $tournamentRepository
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function __construct(MatchSimulatorService $matchSimulatorService,
                              ProbabilityCalculatorService $probabilityCalculatorService,
                              ScheduleGeneratorService $scheduleGeneratorService,
                              MatchRepository $matchRepository,
                              TeamRepository $teamRepository,
                              TournamentRepository $tournamentRepository) {

    $this->matchSimulatorService = $matchSimulatorService;
    $this->probabilityCalculatorService = $probabilityCalculatorService;
    $this->scheduleGeneratorService = $scheduleGeneratorService;
    $this->matchRepository = $matchRepository;
    $this->teamRepository = $teamRepository;
    $this->tournamentRepository = $tournamentRepository;

    $this->matchSimulatorService->generateFirstWeek();
    $this->tournamentData = $this->tournamentRepository->getTournamentData();
    $this->matchesData = $this->matchRepository->getMatchesData();
    $this->probabilities = $this->probabilityCalculatorService->calculateWeekWinProbabilities();
    $this->allProbabilities = $this->probabilityCalculatorService->calculateTournamentWinProbabilities();
    $this->allMatchesData = $this->matchRepository->getAllMatchesData();
    $this->weeksLeft = $this->scheduleGeneratorService->weeksLeft();
  }

  /**
   * @param ContainerInterface $container
   * @return LeagueController|static
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function create(ContainerInterface $container): LeagueController|static {
    /** @var TYPE_NAME $container */
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
   * @return JsonResponse
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
   * @return JsonResponse
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
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function playNewTournament(): JsonResponse {
    $this->tournamentRepository->generateNewTournament();

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * @param Request $request
   * @return JsonResponse
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function saveResult(Request $request): JsonResponse {
    $data = $request->request->all();
    $this->matchSimulatorService->changeResults($data);

    return new JsonResponse(['success' => TRUE, 'status' => 'ok', 'received' => $data]);
  }

  /**
   * @return array
   */
  public function overview(): array {
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
