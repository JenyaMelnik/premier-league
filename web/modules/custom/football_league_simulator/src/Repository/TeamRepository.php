<?php

namespace Drupal\football_league_simulator\Repository;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class TeamRepository {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getTeamIds(): array {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'team')
      ->accessCheck(TRUE);

    $ids = $query->execute();

    return array_values($ids);
  }

}
