-- Use this ONLY if the users table already exists.
-- phpMyAdmin: select database usersolqam_solqam_marketplace -> SQL -> Go

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`, `is_verified`, `api_token`, `created_at`, `updated_at`, `deleted_at`)
VALUES (
  1,
  'Solqam Administrator',
  'admin@solqam.pk',
  '03111222333',
  '$2y$10$sFw.sE16HlLQ5fvL/mV1hO2a7umDzJal45O7F59X2x/2vkrHHfgum',
  'admin',
  'active',
  1,
  'solqamliveadmintoken0123456789ab',
  NOW(),
  NOW(),
  NULL
);

INSERT IGNORE INTO `wallets` (`user_id`, `created_at`, `updated_at`)
VALUES (1, NOW(), NOW());
