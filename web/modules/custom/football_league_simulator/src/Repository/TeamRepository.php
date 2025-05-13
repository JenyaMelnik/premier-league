<?php

namespace Drupal\football_league_simulator\Repository;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class TeamRepository {

  /** @var EntityTypeManagerInterface  */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @return array
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
