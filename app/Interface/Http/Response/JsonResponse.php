<?php

declare(strict_types=1);

namespace App\Interface\Http\Response;

final class JsonResponse
{
  /** @param array<string,mixed> $data */
  public static function send(int $status, array $data): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
  }
}
