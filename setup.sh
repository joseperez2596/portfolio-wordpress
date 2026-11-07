#!/bin/sh
# Fresh install of the portfolio site, end to end. Needs .env (copy .env.example).
# Optional: PORT=8099 COMPOSE_PROJECT_NAME=pf-test ./setup.sh  → a second, independent copy.
set -e
cd "$(dirname "$0")"
set -a; . ./.env; set +a
PORT=${PORT:-8088}; export PORT
URL="http://localhost:$PORT"
W() { docker compose run --rm -T -v "$PWD:/build:ro" wpcli wp "$@"; }

docker compose --profile cli pull -q
docker compose up -d
until curl -s -o /dev/null -w '%{http_code}' "$URL/wp-admin/install.php" | grep -q 200; do sleep 2; done

# WordPress, theme, plugins
W core install --url="$URL" --title="Jose Perez — Software Product Engineer" \
  --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" --admin_email="$WP_ADMIN_EMAIL" --skip-email
W core update            # image can lag behind the latest WordPress release
W core update-db
W theme install hello-elementor --activate
W plugin install elementor fluentform --activate
W plugin delete akismet hello
for t in $(W theme list --status=inactive --field=name --skip-plugins); do W theme delete "$t"; done
W rewrite structure '/%postname%/'
W option update blogdescription "Software Product Engineer"

# Design tokens → Elementor global colors and fonts
W eval-file /build/kit.php

# Home page
PID=$(W post create --post_type=page --post_title=Home --post_status=publish --porcelain --skip-plugins)
W post meta update "$PID" _wp_page_template elementor_canvas
W option update show_on_front page
W option update page_on_front "$PID"
W post delete 1 2 --force

# Images, contact form, page content
for f in assets/*; do W media import "/build/$f" --porcelain --skip-plugins; done
W eval-file /build/form.php
W eval-file /build/scaffold-page.php

echo "Done: $URL  (admin: $URL/wp-admin)"
