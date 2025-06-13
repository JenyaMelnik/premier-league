<?php

use Drupal\node\Entity\Node;

/**
 * Create initial teams after config has been imported.
 */
function football_league_simulator_post_update_create_initial_teams(array &$sandbox): void {
  $teams = [
    ['title' => 'Arsenal', 'team_strength' => 4],
    ['title' => 'Liverpool', 'team_strength' => 5],
    ['title' => 'Manchester city', 'team_strength' => 3],
    ['title' => 'Chelsea', 'team_strength' => 2],
  ];

  foreach ($teams as $team) {
    $node = Node::create([
      'type' => 'team',
      'title' => $team['title'],
      'field_team_strength' => [['value' => $team['team_strength']]],
      'status' => 1,
    ]);
    $node->save();
  }
}
