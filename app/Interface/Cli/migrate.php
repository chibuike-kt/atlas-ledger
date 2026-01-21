<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$db = ($app['db'])();

$dir = $root . '/app/Infrastructure/Migration';
$files = glob($dir . '/*.sql');
sort($files);

try {
  // Ensure migrations table exists (run once)
  $db->exec("
      CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
      ) ENGINE=InnoDB
    ");

  // Load applied migrations
  $applied = [];
  $st = $db->query("SELECT filename FROM schema_migrations");
  foreach ($st->fetchAll() as $row) {
    $applied[$row['filename']] = true;
  }

  foreach ($files as $file) {
    $name = basename($file);

    // Skip already applied
    if (isset($applied[$name])) {
      continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
      throw new RuntimeException("Cannot read migration file: $file");
    }

    $db->exec($sql);

    $ins = $db->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
    $ins->execute([$name]);

    echo "Applied: " . $name . PHP_EOL;
  }

  echo "Done." . PHP_EOL;
} catch (Throwable $e) {
  fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
  exit(1);
}
