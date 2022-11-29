#!/bin/bash

USER="www-data"
GROUP="www-data"

while getopts "u:g:" OPT; do
    case $OPT in
        "u")
            USER="$OPTARG"
            ;;
        "g")
            GROUP="$OPTARG"
            ;;
        *)
            exit 1
    esac
done

# core
git stash
git pull
git stash apply

# plugins

cd plugins || exit 1
for CHK in *; do
    echo "checking ${CHK} ..."
    if [ -d "$CHK" ]; then
        cd "$CHK" || exit 1
        if [ -d ".git" ]; then
            echo -n "updating ${CHK} ... "
            if ! git stash > /dev/null 2>&1; then echo "stash failed"; fi
            if ! git pull > /dev/null 2>&1; then echo "update failed"; fi
            if ! git stash apply > /dev/null 2>&1; then echo "stash restore failed"; fi
            echo "ok"
        else
            echo "${CHK} is not a repository"
        fi
        cd ..
    else
        echo "${CHK} is not a directory"
    fi
done
cd ..

sudo chown -R "${USER}:${GROUP}" ./
sudo chmod -R g+rwX ./
