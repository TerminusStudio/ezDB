/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

INSERT INTO `test` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Program', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(2, 'Something Else', '2021-02-22 00:00:00', '2021-02-22 00:00:00');

INSERT INTO `test2` (`id`, `test_id`, `value`) VALUES
(1, 1, 'Hello'),
(2, 1, 'World'),
(3, 2, 'Hello World');

INSERT INTO `test_intermediate` (`test_id`, `test_related_id`, `intermediate_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'Value', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(1, 2, 'Value2', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(2, 2, 'Value3', '2021-02-22 00:00:00', '2021-02-22 00:00:00');

INSERT INTO `test_related` (`id`, `value`, `created_at`, `updated_at`) VALUES
(1, 'Hello', '2021-02-22 00:00:00', '2021-02-22 00:00:00'),
(2, 'World', '2021-02-22 00:00:00', '2021-02-22 00:00:00');