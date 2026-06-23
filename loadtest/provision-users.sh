#!/usr/bin/env bash
# Generate re-provision SQL for the load-test accounts FROM loadtest/.env variables.
# It sources .env into the shell (never prints the file) and emits SQL on stdout so the
# password only ever flows into the piped SQL stream — pipe it straight to prod MySQL:
#
#   bash loadtest/provision-users.sh | ssh forbes@217.217.253.76 \
#     'CID=$(docker ps --format "{{.Names}}" | grep -i mysql | head -1); \
#      docker exec -i "$CID" bash -c '\''mysql -uroot -p"$(cat "$MYSQL_ROOT_PASSWORD_FILE")" forbes_app'\'''
#
# Accounts use the USERNAMEs in TEST_EMAILS and the single password in TEST_PASSWORDS
# (login matches the username column; first k6 login upgrades MD5->bcrypt, expected).
# NOTE: column set assumes user(username,name,email,password,status,created_at) + Admin
# role via roles.name='Admin'. Adjust the INSERT if `SHOW COLUMNS FROM user` differs.
set -euo pipefail
cd "$(dirname "$0")"
[ -f ./.env ] || { echo "missing loadtest/.env" >&2; exit 1; }
set -a; source ./.env; set +a
: "${TEST_EMAILS:?}"; : "${TEST_PASSWORDS:?}"

IFS=',' read -ra USERS <<< "$TEST_EMAILS"
PW="${TEST_PASSWORDS%%,*}"   # first password (script round-robins one pw across VUs)

echo "SET @admin := (SELECT id FROM roles WHERE name='Admin' ORDER BY id LIMIT 1);"
for u in "${USERS[@]}"; do
  u="$(echo "$u" | xargs)"   # trim
  [ -z "$u" ] && continue
  printf "INSERT INTO user (username,name,email,password,status,created_at) SELECT '%s','%s','%s@loadtest.local',MD5('%s'),'Aktif',NOW() WHERE NOT EXISTS (SELECT 1 FROM user WHERE username='%s');\n" "$u" "$u" "$u" "$PW" "$u"
done
echo "INSERT INTO user_roles (user_id, role_id) SELECT u.id, @admin FROM user u WHERE u.username LIKE 'loadtest%' AND NOT EXISTS (SELECT 1 FROM user_roles r WHERE r.user_id=u.id AND r.role_id=@admin);"
echo "SELECT id, username FROM user WHERE username LIKE 'loadtest%' ORDER BY id;"
