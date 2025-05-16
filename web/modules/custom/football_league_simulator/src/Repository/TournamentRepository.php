<?php

namespace Drupal\football_league_simulator\Repository;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\football_league_simulator\Service\TeamSorterService;
use Drupal\node\Entity\Node;

class TournamentRepository {

  private TeamRepository $teamRepository;
  protected EntityTypeManagerInterface $entityTypeManager;
  private TeamSorterService $teamSorterService;

  public function __construct(
    TeamRepository $teamRepository,
    EntityTypeManagerInterface $entityTypeManager,
    TeamSorterService $teamSorterService
  ) {
    $this->teamRepository = $teamRepository;
    $this->entityTypeManager = $entityTypeManager;
    $this->teamSorterService = $teamSorterService;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getLastTournamentEntityIds(int $numberOfEntities): array|int {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('field_tournament_week', 'DESC')
      ->sort('created', 'DESC')
      ->range(0, $numberOfEntities);

    return $query->execute();
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getTournamentEntityIds(int $numberOfEntities, int $week): array|int {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->condition('field_tournament_week', $week)
      ->accessCheck(TRUE);

    return $query->execute();
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getLastPlayedWeek(): int {
    $nodeId = $this->getLastTournamentEntityIds(1);
    $currentWeek = 0;
    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);

      if ($node->hasField('field_tournament_week')) {
        $currentWeek = $node->get('field_tournament_week')->value;
      }
    }

    return $currentWeek;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getTournamentData(int $week = 0): array {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $countTeams = count($this->teamRepository->getTeamIds());
    if ($week === 0) {
      $query = $this->getLastTournamentEntityIds($countTeams);
    } else {
      $query = $this->getTournamentEntityIds($countTeams, $week);
    }

    $tournamentNodes = $nodeStorage->loadMultiple($query);

    $teamIds = [];
    foreach ($tournamentNodes as $tournament) {
      $teamIds[] = $tournament->get('field_trnmt_team_id')->value;
    }

    // Load teams by their ID.
    $teams = [];
    if (!empty($teamIds)) {
      $teamQuery = $nodeStorage->getQuery()
        ->condition('type', 'team')
        ->condition('nid', $teamIds, 'IN')
        ->accessCheck(TRUE)
        ->execute();

      $teamNodes = $nodeStorage->loadMultiple($teamQuery);

      foreach ($teamNodes as $team) {
        $teams[$team->id()] = $team->get('title')->value;
      }
    }

    // Combine tournament data with team name.
    $tournamentsWithTeams = [];
    foreach ($tournamentNodes as $tournament) {
      $teamId = $tournament->get('field_trnmt_team_id')->value;
      $teamName = $teams[$teamId] ?? 'Unknown Team';

      $tournamentsWithTeams[] = [
        'tournament_id' => $tournament->id(),
        'team_id' => $teamId,
        'team_name' => $teamName,
        'week' => $tournament->get('field_tournament_week')->value,
        'played' => $tournament->get('field_played')->value,
        'points' => $tournament->get('field_points')->value,
        'win' => $tournament->get('field_win')->value,
        'lose' => $tournament->get('field_lose')->value,
        'draw' => $tournament->get('field_draw')->value,
        'goal_difference' => $tournament->get('field_goal_difference')->value,
      ];
      $this->teamSorterService->sortTeams($tournamentsWithTeams);
    }

    return $tournamentsWithTeams;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function saveTournament(
    int $tournamentWeek,
    int $teamId,
    int $teamPoints = 0,
    int $teamWin = 0,
    int $teamLose = 0,
    int $teamDraw = 0,
    int $teamGoalDifference = 0,
    int $playedGame = 0
  ): void {
    $teamPlayed = 0;
    $prevTeamPoints = 0;
    $prevTeamWin = 0;
    $prevTeamLose = 0;
    $prevTeamDraw = 0;
    $prevTeamGoalDifference = 0;
    $lastWeek = $tournamentWeek - 1;

    $nodeId = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->condition('field_trnmt_team_id', $teamId)
      ->condition('field_tournament_week', $lastWeek)
      ->accessCheck(TRUE)
      ->execute();

    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node->hasField('field_played')) {
        $teamPlayed = $node->get('field_played')->value;
      }
      if ($node->hasField('field_points')) {
        $prevTeamPoints = $node->get('field_points')->value;
      }
      if ($node->hasField('field_win')) {
        $prevTeamWin = $node->get('field_win')->value;
      }
      if ($node->hasField('field_lose')) {
        $prevTeamLose = $node->get('field_lose')->value;
      }
      if ($node->hasField('field_draw')) {
        $prevTeamDraw = $node->get('field_draw')->value;
      }
      if ($node->hasField('field_goal_difference')) {
        $prevTeamGoalDifference = $node->get('field_goal_difference')->value;
      }
    }

    // Creating match entity.
    $node = Node::create([
      'type' => 'tournament',
      'title' => 'Week ' . $tournamentWeek . ' team ' . $teamId,
      'field_tournament_week' => $tournamentWeek,
      'field_trnmt_team_id' => $teamId,
      'field_played' => $teamPlayed + $playedGame,
      'field_points' => $prevTeamPoints + $teamPoints,
      'field_win' => $prevTeamWin + $teamWin,
      'field_lose' => $prevTeamLose + $teamLose,
      'field_draw' => $prevTeamDraw + $teamDraw,
      'field_goal_difference' => $prevTeamGoalDifference + $teamGoalDifference,
      'status' => 1,
    ]);

    $node->save();
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function generateNewTournament(): void {
    $this->removeMatchesAndTournament();
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function saveMatchAndTournament(
    int $matchNumber,
    int $tournamentWeek,
    int $team1Id,
    int $team2Id,
    int $team1Score,
    int $team2Score
  ): void {
    $team1Points = 0;
    $team1Wins = 0;
    $team1Lose = 0;
    $team1Draw = 0;
    $team2Points = 0;
    $team2Wins = 0;
    $team2Lose = 0;
    $team2Draw = 0;
    $playedGame = 1;

    // Creating match entity.
    $node = Node::create([
      'type' => 'match',
      'title' => 'Match ' . $matchNumber . ' week ' . $tournamentWeek,
      'field_match_week' => $tournamentWeek,
      'field_team_1_id' => $team1Id,
      'field_team_2_id' => $team2Id,
      'field_score_team_1' => $team1Score,
      'field_score_team_2' => $team2Score,
      'status' => 1,
    ]);

    $node->save();

    // Creating tournament entity.
    $team1GoalDifference = $team1Score - $team2Score;
    $team2GoalDifference = $team2Score - $team1Score;

    if ($team1Score > $team2Score) {
      $team1Points = 3;
      $team1Wins = 1;
      $team2Lose = 1;
    } elseif ($team1Score < $team2Score) {
      $team2Points = 3;
      $team2Wins = 1;
      $team1Lose = 1;
    } elseif ($team1Score === $team2Score) {
      $team1Points = 1;
      $team2Points = 1;
      $team1Draw = 1;
      $team2Draw = 1;
    }

    $this->saveTournament($tournamentWeek, $team1Id, $team1Points, $team1Wins, $team1Lose, $team1Draw, $team1GoalDifference, $playedGame);
    $this->saveTournament($tournamentWeek, $team2Id, $team2Points, $team2Wins, $team2Lose, $team2Draw, $team2GoalDifference, $playedGame);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function removeMatchesAndTournament(int $week = 0): void {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $types = ['match', 'tournament'];

    foreach ($types as $type) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', $type)
        ->accessCheck(FALSE);

      if ($week != 0) {
        if ($type === 'match') {
          $query->condition('field_match_week', $week);
        }
        elseif ($type === 'tournament') {
          $query->condition('field_tournament_week', $week);
        }
      }

      $nids = $query->execute();

      if (!empty($nids)) {
        $nodes = $nodeStorage->loadMultiple($nids);
        foreach ($nodes as $node) {
          $node->delete();
        }
      }
    }
  }

}
