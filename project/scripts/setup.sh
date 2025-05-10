#!/bin/bash
set -e

cd "$(dirname "$0")/.."

source .env
export WP_CLI_PHP_ARGS='-d memory_limit=512M'

ENVIRONMENT="${ENV:-$( [[ "$SITE_URL" == *"localhost"* ]] && echo "local" || echo "prod" )}"
echo "🌍 Environnement : $ENVIRONMENT"

echo "🧼 Nettoyage..."
docker compose down -v || true
sudo rm -rf wp tmp_wordpress generatepress.zip
mkdir -p wp tmp_wordpress

echo "📦 Téléchargement WordPress..."
wget https://wordpress.org/latest.tar.gz -O tmp_wordpress/latest.tar.gz
tar -xzf tmp_wordpress/latest.tar.gz --strip-components=1 -C wp
rm -rf tmp_wordpress

echo "🔐 Permissions initiales sur wp/"
sudo chown -R 33:33 wp
sudo find wp -type d -exec chmod 755 {} \;
sudo find wp -type f -exec chmod 644 {} \;

echo "📁 wp-content/upgrade"
sudo mkdir -p wp/wp-content/upgrade
sudo chown -R 33:33 wp/wp-content
sudo find wp/wp-content -type d -exec chmod 755 {} \;
sudo find wp/wp-content -type f -exec chmod 644 {} \;

echo "⚙️ Création de wp-config.php..."
cat << EOF | sudo tee wp/wp-config.php > /dev/null
<?php
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASSWORD' );
define( 'DB_HOST', 'db:3306' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         '$(openssl rand -base64 32)' );
define( 'SECURE_AUTH_KEY',  '$(openssl rand -base64 32)' );
define( 'LOGGED_IN_KEY',    '$(openssl rand -base64 32)' );
define( 'NONCE_KEY',        '$(openssl rand -base64 32)' );
define( 'AUTH_SALT',        '$(openssl rand -base64 32)' );
define( 'SECURE_AUTH_SALT', '$(openssl rand -base64 32)' );
define( 'LOGGED_IN_SALT',   '$(openssl rand -base64 32)' );
define( 'NONCE_SALT',       '$(openssl rand -base64 32)' );

define( 'FS_METHOD', 'direct' );
\$table_prefix = 'wp_';
EOF

if [ "$ENVIRONMENT" = "local" ]; then
cat << EOF | sudo tee -a wp/wp-config.php > /dev/null
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_HOME', '$SITE_URL' );
define( 'WP_SITEURL', '$SITE_URL' );
EOF
else
cat << EOF | sudo tee -a wp/wp-config.php > /dev/null
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_HOME', '$SITE_URL' );
define( 'WP_SITEURL', '$SITE_URL' );
define( 'FORCE_SSL_ADMIN', true );
define( 'FORCE_SSL_LOGIN', true );
if ( isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) && \$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
  \$_SERVER['HTTPS'] = 'on';
}
EOF
fi
echo "require_once ABSPATH . 'wp-settings.php';" | sudo tee -a wp/wp-config.php > /dev/null

echo "🚀 Lancement des containers..."
docker compose up -d
sleep 20

wpcli() { docker compose run --rm -v "$(pwd)/wp:/var/www/html" wpcli "$@"; }

echo "🛠️ Installation WordPress..."
wpcli core install \
  --url="$SITE_URL" \
  --title="$SITE_TITLE" \
  --admin_user="$ADMIN_USER" \
  --admin_password="$ADMIN_PASSWORD" \
  --admin_email="$ADMIN_EMAIL" \
  --skip-email

echo "🔌 Plugins..."
wpcli plugin install seo-by-rank-math wpforms-lite wp-fastest-cache --activate
if [ "$ENVIRONMENT" = "prod" ]; then
  wpcli plugin install really-simple-ssl ssl-insecure-content-fixer
fi

echo "🚫 Désactivation des commentaires..."
wpcli option update default_comment_status closed
wpcli option update default_ping_status closed

# Ferme les commentaires sur tous les contenus existants
for ID in $(wpcli post list --format=ids); do
  wpcli post update "$ID" --comment_status=closed --ping_status=closed
done



echo "🎨 Installation du thème GeneratePress..."
wget -qO generatepress.zip https://downloads.wordpress.org/theme/generatepress.3.5.1.zip
echo "🔧 Correction des permissions du dossier themes..."
sudo mkdir -p wp/wp-content/themes
sudo chown -R $USER:$USER wp/wp-content/themes
sudo chmod -R 755 wp/wp-content/themes
unzip -q generatepress.zip -d wp/wp-content/themes/
rm generatepress.zip
sudo chown -R 33:33 wp/wp-content/themes/generatepress
sudo find wp/wp-content/themes/generatepress -type d -exec chmod 755 {} \;
sudo find wp/wp-content/themes/generatepress -type f -exec chmod 644 {} \;

echo "👶 Thème enfant..."
mkdir -p wp/wp-content/themes/generatepress-child
sudo mkdir -p wp/wp-content/themes/generatepress-child
sudo chown -R $USER:$USER wp/wp-content/themes/generatepress-child
sudo chmod -R 755 wp/wp-content/themes/generatepress-child
cp ./assets/functions.php wp/wp-content/themes/generatepress-child/functions.php
cat << 'EOF' > wp/wp-content/themes/generatepress-child/style.css
/*
Theme Name: GeneratePress Child
Template: generatepress
Version: 1.0
*/
EOF
sudo chown -R 33:33 wp/wp-content/themes/generatepress-child
sudo mkdir -p wp/wp-content/themes/generatepress-child
sudo chown -R $USER:$USER wp/wp-content/themes/generatepress-child
sudo chmod -R 755 wp/wp-content/themes/generatepress-child
wpcli theme activate generatepress-child

echo "🖼️ Copie des assets..."
sudo mkdir -p wp/wp-content/uploads/custom/css
sudo chown -R $USER:$USER wp/wp-content/uploads/custom
cp -r ./assets/* wp/wp-content/uploads/custom/
cp ./assets/css/styles.css wp/wp-content/uploads/custom/css/styles.css
sudo chown -R 33:33 wp/wp-content/uploads/custom
sudo find wp/wp-content/uploads/custom -type d -exec chmod 755 {} \;
sudo find wp/wp-content/uploads/custom -type f -exec chmod 644 {} \;
sudo chown -R 33:33 wp/wp-content/uploads/custom/css
sudo find wp/wp-content/uploads/custom/css -type d -exec chmod 755 {} \;
sudo find wp/wp-content/uploads/custom/css -type f -exec chmod 644 {} \;
echo "🔁 Permaliens..."
wpcli rewrite structure "/%postname%/"
wpcli rewrite flush --hard

# Créer un menu principal s'il n'existe pas déjà
echo "📋 Création d'un menu principal..."
docker compose run --rm wpcli menu create "Menu Principal" 2>/dev/null || true
docker compose run --rm wpcli menu location assign "Menu Principal" primary 2>/dev/null || true

# Ajouter quelques pages de base
echo "📄 Création de pages de base..."
docker compose run --rm wpcli post create --post_type=page --post_status=publish --post_title="Accueil" --post_content="Bienvenue!"
docker compose run --rm wpcli post create --post_type=page --post_status=publish --post_title="À propos" --post_content="Page à propos"
docker compose run --rm wpcli post create --post_type=page --post_status=publish --post_title="Contact" --post_content="Contactez-nous"

# Configurer la page d'accueil
echo "🏠 Configuration de la page d'accueil..."
HOME_ID=$(docker compose run --rm wpcli post list --post_type=page --name=accueil --field=ID --format=csv | tr -d '\r')
docker compose run --rm wpcli option update page_on_front "$HOME_ID"
docker compose run --rm wpcli option update show_on_front "page"

# Ajouter les pages au menu
echo "🔗 Ajout des pages au menu..."
HOME_ID=$(docker compose run --rm wpcli post list --post_type=page --name=accueil --field=ID --format=csv | tr -d '\r')
ABOUT_ID=$(docker compose run --rm wpcli post list --post_type=page --name=a-propos --field=ID --format=csv | tr -d '\r')
CONTACT_ID=$(docker compose run --rm wpcli post list --post_type=page --name=contact --field=ID --format=csv | tr -d '\r')

docker compose run --rm wpcli menu item add-post "Menu Principal" "$HOME_ID" --title="Accueil" 2>/dev/null || true
docker compose run --rm wpcli menu item add-post "Menu Principal" "$ABOUT_ID" --title="À propos" 2>/dev/null || true
docker compose run --rm wpcli menu item add-post "Menu Principal" "$CONTACT_ID" --title="Contact" 2>/dev/null || true


echo "🛠️ Fixe final des permissions..."
sudo chown -R 33:33 wp
sudo find wp -type d -exec chmod 755 {} \;
sudo find wp -type f -exec chmod 644 {} \;

echo "🔍 🔐 Audit des permissions finales (debug) :"
echo "➡️ wp-content permissions"
ls -l wp/wp-content | grep -E 'themes|uploads|upgrade'
echo "➡️ generatepress/"
ls -l wp/wp-content/themes/generatepress | head
echo "➡️ uploads/custom/"
ls -l wp/wp-content/uploads/custom | head

docker compose restart
echo "✅ Site opérationnel : $SITE_URL"
