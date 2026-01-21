<?php

declare(strict_types=1);

namespace App\Interface\Http\Controller;

final class HealthController
{
  public function index(): void
  {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'service' => 'atlas-ledger']);
  }
}
