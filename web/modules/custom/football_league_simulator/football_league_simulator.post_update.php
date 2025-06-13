<?php

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;

/**
 * Create initial teams after config has been imported.
 */
function football_league_simulator_post_update_create_teams(&$sandbox = NULL): TranslatableMarkup{
  $teams = [
    ['title' => 'Arsenal', 'team_strength' => 4],
    ['title' => 'Liverpool', 'team_strength' => 5],
    ['title' => 'Manchester city', 'team_strength' => 3],
    ['title' => 'Chelsea', 'team_strength' => 2],
  ];

  $created = 0;

  foreach ($teams as $team) {
    // Check if team already exists
    $existing = Drupal::entityQuery('node')
      ->condition('type', 'team')
      ->condition('title', $team['title'])
      ->accessCheck(FALSE)
      ->execute();

    if (empty($existing)) {
      try {
        $node = Node::create([
          'type' => 'team',
          'title' => $team['title'],
          'field_team_strength' => (int)$team['team_strength'],
          'status' => 1,
        ]);
        $node->save();
        $created++;

        Drupal::logger('football_league_simulator')->info('Created team: @title with strength @strength', [
          '@title' => $team['title'],
          '@strength' => $team['team_strength'],
        ]);
      } catch (\Exception $e) {
        Drupal::logger('football_league_simulator')->error('Error creating team @title: @error', [
          '@title' => $team['title'],
          '@error' => $e->getMessage(),
        ]);
      }
    }
  }

  if ($created > 0) {
    return t('Created @count teams.', ['@count' => $created]);
  }
  return t('No new teams were created. They may already exist.');
}
