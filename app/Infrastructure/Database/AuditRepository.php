<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class AuditRepository
{
  public function __construct(private PDO $db) {}

  /** @param array<string,mixed> $meta */
  public function log(?string $actor, string $action, string $entityType, ?string $entityId16, array $meta = []): void
  {
    $st = $this->db->prepare("
          INSERT INTO audit_log (actor, action, entity_type, entity_id, meta_json)
          VALUES (?, ?, ?, ?, ?)
        ");
    $st->execute([
      $actor,
      $action,
      $entityType,
      $entityId16,
      $meta ? json_encode($meta) : null
    ]);
  }
}
