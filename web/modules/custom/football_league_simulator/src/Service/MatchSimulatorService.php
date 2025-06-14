<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\football_league_simulator\Repository\TournamentRepository;

class MatchSimulatorService {

  private ScheduleGeneratorService $scheduleGeneratorService;
  protected TournamentRepository $tournamentRepository;
  private EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    ScheduleGeneratorService $scheduleGeneratorService,
    TournamentRepository $tournamentRepository,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->scheduleGeneratorService = $scheduleGeneratorService;
    $this->tournamentRepository = $tournamentRepository;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function generateFirstWeek(): void {
    $nodeId = $this->tournamentRepository->getLastTournamentEntityIds(1);

    if (empty($nodeId)) {
      $this->generateWeekMatches();
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function generateWeekMatches(): void {
    $tournamentWeek = 1;
    $matchNumber = 1;
    $defaultStrength = 4;

    // Get the last tournament week.
    $nodeId = $this->tournamentRepository->getLastTournamentEntityIds(1);

    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node->hasField('field_tournament_week')) {
        $tournamentWeek = $node->get('field_tournament_week')->value + 1;
      }
    }

    // Getting the week's schedule.
    $weekSchedule = $this->scheduleGeneratorService->generateWeek($tournamentWeek);
    // Play matched and save results.
    foreach ($weekSchedule['matches'] as $match) {

      $team1Id = $match[0];
      $team2Id = $match[1];

      if ($team1Id && $team2Id) {
        $team1 = $this->entityTypeManager->getStorage('node')->load($team1Id);
        $team2 = $this->entityTypeManager->getStorage('node')->load($team2Id);
        $team1Strength = $team1->get('field_team_strength')->value ?? $defaultStrength;
        $team2Strength = $team2->get('field_team_strength')->value ?? $defaultStrength;
        $team1Score = rand(0, $team1Strength);
        $team2Score = rand(0, $team2Strength);

        $this->tournamentRepository->saveMatchAndTournament($matchNumber, $tournamentWeek, $team1Id, $team2Id, $team1Score, $team2Score);
      }

      $matchNumber++;
    }

    if ($weekSchedule['resting_team']) {
      $this->tournamentRepository->saveTournament($tournamentWeek, $weekSchedule['resting_team']);
    }
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function generateAllMatches(): void {
    $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();
    $fullSchedule = $this->scheduleGeneratorService->generateSchedule();
    $totalWeeks = count($fullSchedule);

    for ($week = $lastPlayedWeek + 1; $week <= $totalWeeks; $week++) {
      $this->generateWeekMatches();
    }
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function changeResults(array $data): void {
    if ($data['results']) {
      $lastPlayedWeek = $this->tournamentRepository->getLastPlayedWeek();
      if (count($data['results']) > 1) {
        $this->tournamentRepository->removeMatchesAndTournament();
      } else {
        $this->tournamentRepository->removeMatchesAndTournament($lastPlayedWeek);
      }

      foreach ($data['results'] as $week=>$result) {
        $matchNumber = 1;
        foreach ($result as $match) {

          $teamIds = array_keys($match);
          $teamScores = array_values($match);
          $team1Id = $teamIds[0];
          $team1Score = $teamScores[0];
          $team2Id = $teamIds[1];
          $team2Score = $teamScores[1];

          $this->tournamentRepository->saveMatchAndTournament($matchNumber, $week, $team1Id, $team2Id, $team1Score, $team2Score);

          $matchNumber ++;
        }
      }
    }
  }

}
