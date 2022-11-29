#!/bin/bash

COMPOSER_PATH="/usr/local/bin/composer"
PHP_USER="www-data"
PHP_GROUP="www-data"

while getopts "c:u:g:h" OPT; do
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
        h)
            show_help
            exit 0
            ;;
        *)
            echo "unknown option $OPT"
            exit 1
    esac
done

show_help() {
    cat << EOF
    Setup the ridiculous PHP App Framework Which Exists for No Reason

    Usage:
    $0 [-u <php user> | -g <php group> | -c <composer path> | -h]

    Options:
        -u <php user>       the system user that runs PHP (for r/w access to this folder)
        -g <php group>      the group of the user that runs PHP
        -c <composer path>  absolute path to composer
        -h                  show this guide and exit

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

cp -o utils/settings.json.setup utils/settings.json
cp -o utils/composer_base.json composer/composer.json

if [[ ! -f $COMPOSER_PATH ]]; then
    cd ./composer || exit 1
    echo "installing composer..."
    COMPINST=$( composer_setup )
    if [ "$COMPINST" -gt 0 ]; then
        echo "composer didn't install, i give up."
        exit 1
    else
        echo "installing dependencies ..."
        /usr/local/bin/composer update >> /var/log/tox_composer_update.log 2>&1
    fi
    cd ..
fi

chown -R "${PHP_USER}:${PHP_GROUP}" ./
chmod -R +rwX ./

cat <<EOF > run_update.sh
#!/bin/bash
"$( pwd )/git_update.sh" -u ${PHP_USER} -g ${PHP_GROUP} 2>&1 | tee -a /var/log/tox_update.log
EOF

chmod +x run_update.sh

echo "setup complete. configure your webserver yourself."
echo "to update later, execute run_update.sh"
echo "log in initially with admin / password"

exit 0