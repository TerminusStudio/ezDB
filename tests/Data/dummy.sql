INSERT INTO `test` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Program', '2021-02-22 00:00:00', '2021-02-22 00:00:00');

INSERT INTO `test_intermediate` (`test_id`, `test_related_id`, `intermediate_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'Value', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(1, 2, 'Value2', '2021-02-22 00:00:00', '2021-02-22 00:00:00');

INSERT INTO `test_related` (`id`, `value`, `created_at`, `updated_at`) VALUES
(1, 'Hello', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(2, 'World', '2021-02-22 00:00:00', '2021-02-22 00:00:00');