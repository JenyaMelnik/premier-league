<?php

namespace Drupal\football_league_simulator\Repository;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class MatchRepository {

  protected EntityTypeManagerInterface $entityTypeManager;
  private TournamentRepository $tournamentRepository;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, TournamentRepository $tournamentRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->tournamentRepository = $tournamentRepository;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getMatchesData(?int $week = null): array {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    if ($week === null) {
      $week = $this->tournamentRepository->getLastPlayedWeek();
    }

    $query = $nodeStorage->getQuery()
      ->condition('type', 'match')
      ->condition('status', 1)
      ->accessCheck(TRUE);

    if ($week !== -1) {
      // If -1, then it's all weeks.
      $query->condition('field_match_week', $week);
    }

    $matches = $nodeStorage->loadMultiple($query->execute());

    $teamIds = [];
    foreach ($matches as $match) {
      $team1Id = $match->get('field_team_1_id')->value;
      $team2Id = $match->get('field_team_2_id')->value;
      $teamIds[$team1Id] = $team1Id;
      $teamIds[$team2Id] = $team2Id;
    }

    $teams = [];
    if (!empty($teamIds)) {
      $teamQuery = $nodeStorage->getQuery()
        ->condition('type', 'team')
        ->condition('nid', array_values($teamIds), 'IN')
        ->accessCheck(TRUE)
        ->execute();

      $teamNodes = $nodeStorage->loadMultiple($teamQuery);
      foreach ($teamNodes as $team) {
        $teams[$team->id()] = $team->get('title')->value;
      }
    }

    $result = [];
    foreach ($matches as $match) {
      $week = $match->get('field_match_week')->value;
      $team1Id = $match->get('field_team_1_id')->value;
      $team2Id = $match->get('field_team_2_id')->value;

      $matchData = [
        'match_id' => $match->id(),
        'team_1_name' => $teams[$team1Id] ?? 'Unknown',
        'team_1_id' => $team1Id,
        'team_2_name' => $teams[$team2Id] ?? 'Unknown',
        'team_2_id' => $team2Id,
        'score_team_1' => $match->get('field_score_team_1')->value,
        'score_team_2' => $match->get('field_score_team_2')->value,
      ];

      $result[$week][] = $matchData;
    }

    return $result;
  }

}
