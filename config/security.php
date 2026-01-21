<?php
return [
  'api_keys' => array_values(array_filter(array_map('trim', explode(',', getenv('API_KEYS') ?: '')))),

  'admin_keys' => array_values(array_filter(array_map(
    'trim',
    explode(',', getenv('ADMIN_API_KEYS') ?: '')
  ))),

];
