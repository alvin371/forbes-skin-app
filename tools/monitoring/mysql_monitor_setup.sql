-- Run once as a MySQL administrator. Replace the placeholder locally; never commit
-- the password. '%' is required because the app/monitor connect from Docker networks.
CREATE USER IF NOT EXISTS 'forbes_monitor'@'%' IDENTIFIED BY 'REPLACE_WITH_LONG_RANDOM_PASSWORD';
GRANT PROCESS ON *.* TO 'forbes_monitor'@'%';
GRANT SELECT ON performance_schema.* TO 'forbes_monitor'@'%';
FLUSH PRIVILEGES;

-- Store the credentials outside this repository, e.g.
-- /home/forbes/.config/forbes-mysql-monitor.cnf (mode 0600):
-- [client]
-- user=forbes_monitor
-- password=REPLACE_WITH_LONG_RANDOM_PASSWORD
-- host=mysql-8_mysql
