#!/bin/bash

# this script makes a lot of assumptions (deal with it lol):
#   - you are using Debian or a Debian-based distro
#   - you are using nginx and php-fpm
#
# this script will install packages and attempt to
# automatically configure both NGINX and PHP. You can
# see detailed usage instructions by running with the -h
# flag (or just looking at show_help() below)

COMPOSER_PATH="/usr/local/bin/composer"
PHP_USER="www-data"
PHP_GROUP="www-data"
PHP_VER="7.4"
APP_PORT="7076"
SITENAME="localhost"
SITEDIR="$( pwd )"

while getopts "c:u:g:s:v:P:hnp" OPT; do
    case $OPT in
        u)
            PHP_USER="${OPTARG}"
            ;;
        g)
            PHP_GROUP="${OPTARG}"
            ;;
        c)
            COMPOSER_PATH="${OPTARG}"
            ;;
        s)
            SITENAME="${OPTARG}"
            ;;
        v)
            PHP_VER="${OPTARG}"
            ;;
        P)
            APP_PORT="${OPTARG}"
            ;;
        h)
            show_help
            exit 0
            ;;
        n)
            NO_NGINX="true"
            ;;
        p)
            NO_PHP="true"
            ;;
        *)
            echo "unknown option $OPT"
            exit 1
    esac
done

php_conf() {
    apt -y install git php-fpm php-gd php-json php-yaml php-imagick php-mbstring jq
    cat << EOF > "/etc/php/${PHP_VER}/fpm/pool.d/${SITENAME}.conf"
[${SITENAME}]
user = ${PHP_USER}
group = ${PHP_GROUP}
listen = /run/php/${SITENAME}-fpm.sock
listen.owner = ${PHP_USER}
listen.group = ${PHP_GROUP}
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF
    systemctl enable "php${PHP_VER}-fpm.service"
    systemctl restart "php${PHP_VER}-fpm.service"
}

nginx_conf() {
    apt -y install nginx
    [[ -d /var/log/nginx ]] || mkdir -p /var/log/nginx
    [[ -d /etc/nginx/sites-enabled ]] || mkdir -p /etc/nginx/sites-enabled
    [[ $( which nginx ) ]] || apt -y install nginx
    cat << EOF > "/etc/nginx/sites-enabled/${SITENAME}.conf"
server {
    listen                  ${APP_PORT};
    access_log              /var/log/nginx/${SITENAME}-access.log;
    error_log               /var/log/nginx/${SITENAME}-error.log;
    server_name             localhost;
    index                   index.html index.htm index.php;
    root                    /var/www/${SITEDIR};
    location ~*\ \.(png|jpg|jpeg|svg|gif|ico|js)$ {
        expires 7d;
    }
    location /              {
        try_files       index.php @dynamic;
    }
    location @dynamic       {
        fastcgi_pass                unix:/run/php/${SITENAME}-fpm.sock;
        fastcgi_buffers             16  32k;
        fastcgi_buffer_size         64k;
        fastcgi_busy_buffers_size   64k;
        include                     fastcgi_params;
        fastcgi_param               PATH_INFO       \$uri;
        fastcgi_param               REQUEST_URI     \$request_uri;
        fastcgi_param               SCRIPT_NAME     /index.php;
        fastcgi_param               SCRIPT_FILENAME /var/www/${SITEDIR}/index.php;
    }
}
EOF
    systemctl reload nginx
}

show_help() {
    cat << EOF
    Setup the ridiculous PHP App Framework Which Exists for No Reason

    Usage:
    $0 [u:g:c:s:v:P:nph]

    Options:
        -u <php user>       the system user that runs PHP (for r/w access to this folder)
        -g <php group>      the group of the user that runs PHP
        -c <composer path>  absolute path to composer
        -s <site name>      specify server_name (default localhost)
        -v <php version>    specify PHP version in use (default 7.4)
        -P <port>           specify the port to listen on (default 7076)
        -n                  do not autoconfigure nginx
        -p                  do not autoconfigure php-fpm
        -h                  show this guide and exit
    
    -s <site name> controls what log files and config files are named
    -v <php version> doesn't install that version, it's just to know what the fpm service is called.

    This will install Composer dependencies (including Composer itself if
    it can't be found). It assumes your php user and group are both
    "www-data", so use the flags if that is not true in your case.

    Assuming it works, you should be able to log in to the app using the
    username "admin" and password "password".
EOF
}

setup_composer() {
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
    then
        >&2 echo 'ERROR: Invalid installer checksum'
        rm composer-setup.php
        echo 1
    fi

    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    RESULT=$?
    rm composer-setup.php
    echo $RESULT
}

if [ -f utils/settings.json ]; then
    echo "it looks like you're already set up. if you want to rerun this, delete utils/settings.json and try again."
    exit 0
fi

if [[ "$( whoami )" != "root" ]]; then
    echo "run me as root."
    exit 1
fi

if [[ -z "$( which php )" ]]; then
    echo "install PHP, please."
    exit 1
fi

if [[ ! -f "utils/settings.json.setup" ]]; then
    echo "please run from the root of the project folder."
    exit 1
fi

cp utils/settings.json.setup utils/settings.json
cp data/users.json.setup data/users.json
cp utils/composer_base.json composer/composer.json

if [[ ! -f $COMPOSER_PATH ]]; then
    echo "installing composer..."
    cd ./composer || exit 1
    COMPINST=$( setup_composer )
    if [[ "$COMPINST" -gt 0 ]]; then
        echo "composer didn't install, i give up."
        exit 1
    fi
    cd ..
fi

if [[ ! -d composer/vendor ]]; then echo "installing composer dependendencies..."; cd composer; ${COMPOSER_PATH} update; cd ..; fi

chown -R "${PHP_USER}:${PHP_GROUP}" ./
chmod -R +rwX ./

cat <<EOF > run_update.sh
#!/bin/bash
"$( pwd )/git_update.sh" -u ${PHP_USER} -g ${PHP_GROUP} 2>&1 | tee -a /var/log/tox_update.log
EOF
chmod +x run_update.sh

if [[ -z $NO_NGINX ]]; then
    nginx_conf
    echo "nginx setup done"
else
    echo "nginx setup skipped, do it yourself."
fi

if [[ -z $NO_PHP ]]; then
    php_conf
    echo "php-fpm setup done"
else
    echo "php-fpm setup skipped, do it yourself."
fi

cat << EOF
    app setup complete (we hope)
    log in initially with admin / password
EOF

exit 0