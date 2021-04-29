DROP TABLE IF EXISTS test;
CREATE TABLE IF NOT EXISTS test (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(1024) NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS test2;
CREATE TABLE IF NOT EXISTS test2 (
  id int NOT NULL,
  test_id int NOT NULL,
  value varchar(255) NOT NULL
);

DROP TABLE IF EXISTS test_intermediate;
CREATE TABLE IF NOT EXISTS test_intermediate (
  test_id int NOT NULL,
  test_related_id int NOT NULL,
  intermediate_value varchar(1024) DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL
);

DROP TABLE IF EXISTS test_related;
CREATE TABLE IF NOT EXISTS test_related (
  id int NOT NULL AUTO_INCREMENT,
  value varchar(1024) NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

