#!/bin/bash

COMPOSER_PATH="/usr/local/bin/composer"

while getopts "u:g:" OPT; do
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
    esac
done

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
    echo "installing composer..."
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
    if [ $? -gt 0 ]; then
        echo "composer didn't install, i give up."
        exit 1
    else
        echo "installing dependencies ..."
        /usr/local/bin/composer update >> /var/log/tox_composer_update.log 2>&1
    fi
fi

if [ -z $PHP_USER ]; then read -r -p "PHP User: " PHP_USER; fi
if [ -z $PHP_GROUP]; then read -r -p "PHP Group: " PHP_GROUP; fi

chown -R ${PHP_USER}:${PHP_GROUP} ./
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