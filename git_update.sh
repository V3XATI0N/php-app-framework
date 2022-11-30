#!/bin/bash

# this script updates your app installation by pulling new commits from git.
# HOWEVER, it is not immensely intelligent. the process it uses is a little
# sloppy - it stashes whatever has changed, updates the base install, then
# reapplies your stash. Git ignores /almost/ all the files that might
# change, but you may get into a situation where your changes conflict with
# updates (making changes to plugin.json files is almost guaranteed to cause
# this to happen).

CONSOLE_NAME="{{ console_name }}"
CONSOLE_PATH="$( pwd )"
PHP_USER="www-data"
PHP_GROUP="www-data"
PLUGIN_LIST="None"

while getops "u:g:p:PCh" OPT; do
    case $OPT in
        u) PHP_USER=$OPTARG;;
        g) PHP_GROUP=$OPTARG;;
        p) PLUGIN_LIST="$OPTARG";;
        P) PLUGINS_ONLY="True";;
        C) CORE_ONLY="True";;
        h) show_help; exit 0;;
        *) echo "invalid option $OPT"; exit 1
    esac
done

show_help() {
    cat << EOF
UPDATE STUFF LOL

    This updates your installation by pulling new stuff from git. It spits out its logs
    in the file /var/log/v5update.log because being vague is my passion.

    Usage:
        $0 [u:g:p:PCh]

    Options:
        -u      <php user> (default $PHP_USER)
        -g      <php group> (default $PHP_GROUP)
        -p      comma-separated list of plugin names to update (otherwise they'll all be updated anyway)
        -P      only update plugins, don't touch Core
        -C      only update Core, don't touch plugins
        -h      show this and leave
EOF
}

RUN_CORE="TRUE"
RUN_PLUGINS="TRUE"

if [ "${PLUGINS_ONLY}" == "True" ]; then RUN_PLUGINS="TRUE"; RUN_CORE="FALSE"; fi
if [ "${CORE_ONLY}" == "True" ]; then RUN_CORE="TRUE"; RUN_PLUGINS="FALSE"; fi

if [ -d "${CONSOLE_PATH}" ]; then
    cd "${CONSOLE_PATH}" || exit 1
    if [ "${RUN_CORE}" == "TRUE" ]; then
        echo -n "updating ${CONSOLE_NAME} / core ... "
        git stash >> /var/log/v5update.log 2>&1
        git pull >> /var/log/v5update.log 2>&1
        if [ $? -gt 0 ]; then echo "GIT MERGE ERROR IN CORE!"; exit 2; fi
        git stash apply >> /var/log/v5update.log 2>&1
        echo "ok"
    else
        echo "SKIPPING ${CONSOLE_NAME} / core (plugins_only)"
    fi
    if [ "${RUN_PLUGINS}" == "TRUE" ]; then
        echo "updating ${CONSOLE_NAME} / plugins ... "
        cd plugins || exit 1
        for pd in *; do
            pd_ok="FALSE"
            if [ "${PLUGIN_LIST}" != "None" ]; then
                IFS=','; for pi in ${PLUGIN_LIST}; do
                    if [ "${pi}" == "${pd}" ]; then
                        pd_ok="TRUE"
                    fi
                done
            else
                pd_ok="TRUE"
            fi
            if [ "$pd_ok" == "TRUE" ] && [ -d "${pd}/.git" ]; then
                echo -n "updating ${CONSOLE_NAME} / plugins / ${pd} ... "
                cd "${pd}" || exit 1
                if ! git stash >> /var/log/v5update.log 2>&1; then echo "git stash error in ${pd}"; exit 3; fi
                if ! git pull >> /var/log/v5update.log 2>&1; then echo "git merge error in ${pd}"; exit 4; fi
                if ! git stash apply >> /var/log/v5update.log 2>&1; then echo "git stash-apply error in ${pd}"; echo 5; fi
                cd ..
                echo "ok"
            fi
        done
    else
        echo "SKIPPING ${CONSOLE_NAME} / plugins (core_only)"
    fi
    chown -R "${PHP_USER}:${PHP_GROUP}" "${CONSOLE_PATH}"
    chmod -R ug+rwX "${CONSOLE_PATH}"
else
    echo "no such path ${CONSOLE_PATH}"
    exit 1
fi
