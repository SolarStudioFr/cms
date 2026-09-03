-- doctrine.yaml's when@test config appends a `_test` suffix to the same
-- DATABASE_URL/user (see cms/config/packages/doctrine.yaml), so the `app`
-- user needs access to a database it isn't otherwise granted on.
CREATE DATABASE IF NOT EXISTS `app_test` CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON `app_test`.* TO 'app'@'%';
FLUSH PRIVILEGES;
