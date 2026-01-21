<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class RateLimitRepository
{
  public function __construct(private PDO $db) {}

  /**
   * Atomically increments hits if within window, resets if window passed.
   * Returns [hits, resetAtUnix].
   */
  public function hit(string $key, int $limit, int $windowSeconds): array
  {
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $resetAt = $now->modify("+{$windowSeconds} seconds");
    $resetAtStr = $resetAt->format('Y-m-d H:i:s.u');

    // Insert if missing
    $st = $this->db->prepare("
          INSERT INTO rate_limits (k, hits, reset_at)
          VALUES (?, 1, ?)
          ON DUPLICATE KEY UPDATE
            hits = IF(reset_at <= UTC_TIMESTAMP(6), 1, hits + 1),
            reset_at = IF(reset_at <= UTC_TIMESTAMP(6), VALUES(reset_at), reset_at)
        ");
    $st->execute([$key, $resetAtStr]);

    // Read current
    $st2 = $this->db->prepare("SELECT hits, reset_at FROM rate_limits WHERE k=? LIMIT 1");
    $st2->execute([$key]);
    $row = $st2->fetch();

    $hits = (int)$row['hits'];
    $resetAtUnix = (new \DateTimeImmutable($row['reset_at'], new \DateTimeZone('UTC')))->getTimestamp();

    return [$hits, $resetAtUnix];
  }

  public function isLimited(int $hits, int $limit): bool
  {
    return $hits > $limit;
  }
}
