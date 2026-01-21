<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use Throwable;

final class Tx
{
  /** @template T */
  public static function run(PDO $db, callable $fn)
  {
    $db->beginTransaction();
    try {
      $result = $fn();
      $db->commit();
      return $result;
    } catch (Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      throw $e;
    }
  }
}
