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
    esac
done

# core
git stash
git pull
git stash apply

# plugins

cd plugins
for CHK in $( ls ./ ); do
    echo "checking ${CHK} ..."
    if [ -d "$CHK" ]; then
        cd "$CHK"
        if [ -d ".git" ]; then
            echo -n "updating ${CHK} ... "
            git stash > /dev/null 2>&1
            if [ $? -gt 0 ]; then echo "stash failed"; fi
            git pull > /dev/null 2>&1
            if [ $? -gt 0 ]; then echo "update failed"; fi
            git stash apply > /dev/null 2>&1
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

sudo chown -R ${USER}:${GROUP} ./
sudo chmod -R g+rwX ./
