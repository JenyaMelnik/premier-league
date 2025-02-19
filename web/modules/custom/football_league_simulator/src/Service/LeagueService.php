<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\node\Entity\Node;

class LeagueService {

  private $team1Id;
  private $team2Id;

  public function generateWeek($week) {
    $schedule = $this->generateSchedule();

    return $schedule[$week];
  }

  public function generateSchedule(): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'team')
      ->accessCheck(TRUE)
      ->execute();

    $teams = Node::loadMultiple($query);
    $teamIds = array_keys($teams);

    // Check if the number of teams is odd.
    if (count($teamIds) % 2 != 0) {
      $teamIds[] = null; // Add an ‘empty’ command if an odd number.
    }

    $numTeams = count($teamIds);
    $totalRounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;
    $schedule = [];

    // Generation of the first round.
    for ($round = 0; $round < $totalRounds; $round++) {
      $roundMatches = [];
      for ($match = 0; $match < $matchesPerRound; $match++) {
        $home = ($round + $match) % ($numTeams - 1);
        $away = ($numTeams - 1 - $match + $round) % ($numTeams - 1);

        // The last team always stays in place while the others rotate.
        if ($match == 0) {
          $away = $numTeams - 1;
        }

        // Add a match if neither team is ‘null’.
        if ($teamIds[$home] !== null && $teamIds[$away] !== null) {
          $roundMatches[] = [$teamIds[$home], $teamIds[$away]];
        }
      }
      $schedule[$round + 1] = $roundMatches;
    }

    // Generation of the second round (change hosts and guests).
    $secondRoundStart = count($schedule) + 1;
    foreach ($schedule as $roundMatches) {
      $returnRound = [];
      foreach ($roundMatches as $match) {
        $returnRound[] = [$match[1], $match[0]]; // Change hosts and guests.
      }
      $schedule[$secondRoundStart++] = $returnRound;
    }

    return $schedule;
  }

  public function generateMatch() {

    $tournamentWeek = 0;
    $matchNumber = 1;
    $team1Points = 0;
    $team1Wins = 0;
    $team1Lose = 0;
    $team1Draw = 0;
    $team1GoalDifference = 0;
    $team2Points = 0;
    $team2Wins = 0;
    $team2Lose = 0;
    $team2Draw = 0;
    $team2GoalDifference = 0;

    // Get the last tournament week.
    $nodeId = \Drupal::entityQuery('node')
      ->condition('type', 'tournament')
      ->condition('status', 1) // Только опубликованные ноды
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = Node::load($nid);
      if ($node->hasField('field_tournament_week')) {
        $tournamentWeek = $node->get('field_tournament_week')->value;
      }
    }

    // Getting the week's schedule.
    $weekSchedule = $this->generateWeek($tournamentWeek +1);

    // Play matched and save results.
    foreach ($weekSchedule as $match) {
      $team1Id = $match[0];
      $team2Id = $match[1];

      if ($team1Id && $team2Id) {
        $team1 = Node::load($team1Id);
        $team2 = Node::load($team2Id);
        $team1Strength = $team1->get('field_team_strength')->value;
        $team2Strength = $team2->get('field_team_strength')->value;
        $team1Score = rand(0, $team1Strength);
        $team2Score = rand(0, $team2Strength);

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
        $team1GoalDifference = $team1GoalDifference - $team2GoalDifference;
        $team2GoalDifference = $team2GoalDifference - $team1GoalDifference;

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
      }

      $this->saveTournament($tournamentWeek, $team1Id, $team1Points, $team1Wins, $team1Lose, $team1Draw, $team1GoalDifference);
      $this->saveTournament($tournamentWeek, $team2Id, $team2Points, $team2Wins, $team2Lose, $team2Draw, $team2GoalDifference);

      $matchNumber++;
    }
  }

  public function saveTournament($tournamentWeek, $teamId, $teamPoints, $teamWin, $teamLose, $teamDraw, $teamGoalDifference) {

    $teamPlayed = 0;
    $prevTeamPoints = 0;
    $prevTeamWin = 0;
    $prevTeamLose = 0;
    $prevTeamDraw = 0;
    $prevTeamGoalDifference = 0;
    $tournamentWeek = $tournamentWeek + 1;

    $nodeId = \Drupal::entityQuery('node')
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->condition('field_trnmt_team_id', $teamId)
      ->condition('field_tournament_week', $tournamentWeek)
      ->accessCheck(TRUE)
      ->execute();

    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = Node::load($nid);
      if ($node->hasField('field_played')) {
        $teamPlayed = $node->get('field_played')->value;
      } elseif ($node->hasField('field_points')) {
        $prevTeamPoints = $node->get('field_points')->value;
      } elseif ($node->hasField('field_win')) {
        $prevTeamWin = $node->get('field_win')->value;
      } elseif ($node->hasField('field_lose')) {
        $prevTeamLose = $node->get('field_lose')->value;
      } elseif ($node->hasField('field_draw')) {
        $prevTeamDraw = $node->get('field_draw')->value;
      } elseif ($node->hasField('field_goal_difference')) {
        $prevTeamGoalDifference = $node->get('field_goal_difference')->value;
      }
    }

    // Creating match entity.
    $node = Node::create([
      'type' => 'tournament',
      'title' => 'Week ' . $tournamentWeek . ' team ' . $teamId,
      'field_tournament_week' => $tournamentWeek,
      'field_trnmt_team_id' => $teamId,
      'field_played' => $teamPlayed + 1,
      'field_points' => $prevTeamPoints + $teamPoints,
      'field_win' => $prevTeamWin + $teamWin,
      'field_lose' => $prevTeamLose + $teamLose,
      'field_draw' => $prevTeamDraw + $teamDraw,
      'field_goal_difference' => $prevTeamGoalDifference + $teamGoalDifference,
      'status' => 1,
    ]);

    $node->save();
  }
}
