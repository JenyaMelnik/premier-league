<?php

namespace Drupal\football_league_simulator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

class LeagueService {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function generateWeek($week) {
    $schedule = $this->generateSchedule();

    return $schedule[$week];
  }

  public function getTeamIds() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'team')
      ->accessCheck(TRUE);

    $ids = $query->execute();

    return array_values($ids);
  }

  public function getLastTournamentEntityIds($numberOfEntities) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('field_tournament_week', 'DESC')
      ->sort('created', 'DESC')
      ->range(0, $numberOfEntities);

    return $query->execute();
  }

  public function getTournamentEntityIds($numberOfEntities, $week) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tournament')
      ->condition('status', 1)
      ->condition('field_tournament_week', $week)
      ->accessCheck(TRUE);

    return $query->execute();
  }

  public function getLastPlayedWeek() {
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

  public function generateSchedule(): array {
    $teamIds = $this->getTeamIds();

    // Check if the number of teams is odd.
    if (count($teamIds) % 2 != 0) {
      $teamIds[] = null; // Add an ‘empty’ command if an odd number.
    }

    $numTeams = count($teamIds);
    $totalRounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;
    $schedule = [];
    $restingTeam = null;

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

        if ($teamIds[$home] === null) {
          $restingTeam = $teamIds[$away];
        } elseif ($teamIds[$away] === null) {
          $restingTeam = $teamIds[$home];
        } else {
          $roundMatches[] = [$teamIds[$home], $teamIds[$away]];
        }
      }
      $schedule[$round + 1] = [
        'matches' => $roundMatches,
        'resting_team' => $restingTeam,
      ];
    }

    // Generation of the second round (change hosts and guests).
    $secondRoundStart = count($schedule) + 1;

    foreach ($schedule as $roundData) {
      $returnRound = [];
      foreach ($roundData['matches'] as $match) {
        $returnRound[] = [$match[1], $match[0]]; // Change hosts and guests.
      }

      $schedule[$secondRoundStart++] = [
        'matches' => $returnRound,
        'resting_team' => $roundData['resting_team'],
      ];
    }

    return $schedule;
  }

  public function weeksLeft($week = 0) {
    $schedule = $this->generateSchedule();
    $totalWeeks = count($schedule);

    $lastPlayedWeek = $this->getLastPlayedWeek();

    if ($week == 0) {
      return $totalWeeks - $lastPlayedWeek;
    } else {
      return $totalWeeks - $week;
    }

  }

  public function generateFirstWeek() {
    $nodeId = $this->getLastTournamentEntityIds(1);

    if (empty($nodeId)) {
      $this->generateWeekMatches();
    }
  }

  public function generateWeekMatches() {

    $tournamentWeek = 1;
    $matchNumber = 1;

    // Get the last tournament week.
    $nodeId = $this->getLastTournamentEntityIds(1);

    if (!empty($nodeId)) {
      $nid = reset($nodeId);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node->hasField('field_tournament_week')) {
        $tournamentWeek = $node->get('field_tournament_week')->value + 1;
      }
    }

    // Getting the week's schedule.
    $weekSchedule = $this->generateWeek($tournamentWeek);
    // Play matched and save results.
    foreach ($weekSchedule['matches'] as $match) {

      $team1Id = $match[0];
      $team2Id = $match[1];

      if ($team1Id && $team2Id) {
        $team1 = $this->entityTypeManager->getStorage('node')->load($team1Id);
        $team2 = $this->entityTypeManager->getStorage('node')->load($team2Id);
        $team1Strength = $team1->get('field_team_strength')->value;
        $team2Strength = $team2->get('field_team_strength')->value;
        $team1Score = rand(0, $team1Strength);
        $team2Score = rand(0, $team2Strength);

        $this->saveMatchAndTournament($matchNumber, $tournamentWeek, $team1Id, $team2Id, $team1Score, $team2Score);

      }

      $matchNumber++;
    }

    if ($weekSchedule['resting_team']) {
      $this->saveTournament($tournamentWeek, $weekSchedule['resting_team']);
    }
  }

  public function saveMatchAndTournament($matchNumber, $tournamentWeek, $team1Id, $team2Id, $team1Score, $team2Score) {
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

  public function changeResults($data) {
    if ($data['results']) {
      $lastPlayedWeek = $this->getLastPlayedWeek();
      if (count($data['results']) > 1) {
        $this->removeMatchesAndTournament();
      } else {
        $this->removeMatchesAndTournament($lastPlayedWeek);
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

          $this->saveMatchAndTournament($matchNumber, $week, $team1Id, $team2Id, $team1Score, $team2Score);

          $matchNumber ++;
        }
      }
    }
  }

  public function generateAllMatches() {
    $lastPlayedWeek = $this->getLastPlayedWeek();
    $fullSchedule = $this->generateSchedule();
    $totalWeeks = count($fullSchedule);

    for ($week = $lastPlayedWeek + 1; $week <= $totalWeeks; $week++) {
      $this->generateWeekMatches();
    }
  }

  public function saveTournament($tournamentWeek, $teamId, $teamPoints = 0, $teamWin = 0, $teamLose = 0, $teamDraw = 0, $teamGoalDifference = 0, $playedGame = 0) {

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

  public function getTournamentData($week = 0) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $countTeams = count($this->getTeamIds());
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
      usort($tournamentsWithTeams, function ($a, $b) {
        if ($a['points'] === $b['points']) {
          if ($a['goal_difference'] === $b['goal_difference']) {
            return strcmp($a['team_name'], $b['team_name']);
          }
          return $b['goal_difference'] <=> $a['goal_difference'];
        }
        return $b['points'] <=> $a['points'];
      });
    }

    return $tournamentsWithTeams;
  }

  function calculateWeekWinProbabilities() {
    $gamesLeft = $this->weeksLeft();
    $lastPlayedWeek = $this->getLastPlayedWeek();

    if ($lastPlayedWeek < 4) {
      return [];
    }

    $teams = $this->getTournamentData();
    if (!$teams) {
      return [];
    }

    $maxPoints = max(array_column($teams, 'points'));
    $totalRating = 0;
    $ratings = [];

    foreach ($teams as $team) {
      $baseRating = $team['points'] * 100;
      $goalFactor = $team['goal_difference'] * 5;

      $rating = $baseRating + $goalFactor;

      if (($maxPoints - $team['points']) > (3 * $gamesLeft)) {
        $rating = 0;
      }

      $ratings[] = [
        'team' => $team['team_name'],
        'points' => $team['points'],
        'goal_difference' => $team['goal_difference'],
        'rating' => $rating
      ];
      $totalRating += $rating;
    }

    // Вычисляем вероятность победы
    $probabilities = [];

    if ($gamesLeft === 0) {
      usort($ratings, function ($a, $b) {
        if ($b['points'] !== $a['points']) {
          return $b['points'] - $a['points'];
        }
        return $b['goal_difference'] - $a['goal_difference'];
      });

      $topTeam = $ratings[0];
      $winners = array_filter($ratings, function ($team) use ($topTeam) {
        return $team['points'] === $topTeam['points'] && $team['goal_difference'] === $topTeam['goal_difference'];
      });

      foreach ($ratings as $team) {
        $probabilities[$lastPlayedWeek][] = [
          'team' => $team['team'],
          'probability' => isset($winners[$team['team']]) ? 100.0 : 0.0
        ];
      }
    } else {
      foreach ($ratings as $team) {
        $probability = $totalRating > 0 ? round($team['rating'] / $totalRating * 100, 2) : 0;
        $probabilities[$lastPlayedWeek][] = [
          'team' => $team['team'],
          'probability' => $probability
        ];
      }
    }

    return $probabilities;
  }

  function calculateTournamentWinProbabilities() {
    $lastPlayedWeek = $this->getLastPlayedWeek();
    $probabilitiesByWeek = [];

    if ($lastPlayedWeek < 4) {
      return [];
    }

    for ($week = 4; $week <= $lastPlayedWeek; $week++) {
      $gamesLeft = $this->weeksLeft($week);
      $teams = $this->getTournamentData($week);

      if (!$teams) {
        continue;
      }

      $maxPoints = max(array_column($teams, 'points'));
      $totalRating = 0;
      $ratings = [];

      foreach ($teams as $team) {
        $baseRating = $team['points'] * 100;
        $goalFactor = $team['goal_difference'] * 5;
        $rating = $baseRating + $goalFactor;

        if (($maxPoints - $team['points']) > (3 * $gamesLeft)) {
          $rating = 0;
        }

        $ratings[] = [
          'team' => $team['team_name'],
          'points' => $team['points'],
          'goal_difference' => $team['goal_difference'],
          'rating' => $rating
        ];
        $totalRating += $rating;
      }

      $weekProbabilities = [];

      // if
      if ($gamesLeft === 0) {
        usort($ratings, function ($a, $b) {
          if ($b['points'] !== $a['points']) {
            return $b['points'] - $a['points'];
          }
          return $b['goal_difference'] - $a['goal_difference'];
        });

        $topTeam = $ratings[0];
        $winners = array_filter($ratings, function ($team) use ($topTeam) {
          return $team['points'] === $topTeam['points'] && $team['goal_difference'] === $topTeam['goal_difference'];
        });

        foreach ($ratings as $team) {
          $weekProbabilities[] = [
            'team' => $team['team'],
            'probability' => in_array($team, $winners) ? 100.0 : 0.0
          ];
        }
      } else {
        foreach ($ratings as $team) {
          $probability = $totalRating > 0 ? round($team['rating'] / $totalRating * 100, 2) : 0;
          $weekProbabilities[] = [
            'team' => $team['team'],
            'probability' => $probability
          ];
        }
      }

      $probabilitiesByWeek[$week] = $weekProbabilities;
    }

    return $probabilitiesByWeek;
  }

  public function getMatchesData() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $currentWeek = $this->getLastPlayedWeek();

    $matchQuery = $nodeStorage->getQuery()
      ->condition('type', 'match')
      ->condition('status', 1)
      ->condition('field_match_week', $currentWeek)
      ->accessCheck(TRUE)
      ->execute();

    $currentWeekMatches = $nodeStorage->loadMultiple($matchQuery);

    $teamIds = [];
    foreach ($currentWeekMatches as $match) {
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
    foreach ($currentWeekMatches as $match) {
      $team1Id = $match->get('field_team_1_id')->value;
      $team2Id = $match->get('field_team_2_id')->value;

      $result[$currentWeek][] = [
        'match_id' => $match->id(),
        'team_1_name' => $teams[$team1Id] ?? 'Unknown',
        'team_1_id' => $team1Id,
        'team_2_name' => $teams[$team2Id] ?? 'Unknown',
        'team_2_id' => $team2Id,
        'score_team_1' => $match->get('field_score_team_1')->value,
        'score_team_2' => $match->get('field_score_team_2')->value,
      ];
    }

    return $result;
  }

  public function getAllMatchesData() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $matchQuery = $nodeStorage->getQuery()
      ->condition('type', 'match')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->execute();

    $matches = $nodeStorage->loadMultiple($matchQuery);

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

  public function generateNewTournament() {
    $this->removeMatchesAndTournament();
  }

  public function removeMatchesAndTournament($week = 0) {
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
