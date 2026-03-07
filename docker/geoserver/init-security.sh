#!/bin/bash
# Initialize missing GeoServer security files
# These files are not tracked in git (public repo) and must be generated per environment.
# GeoServer will regenerate geoserver.jceks and masterpw.digest on first startup
# if masterpw/default/passwd and usergroup/default/users.xml exist.
#
# Credential handling:
# The official image's startup chain (startup.sh -> handle_geoserver_admin_credentials.sh ->
# update_credentials.sh) overwrites users.xml/roles.xml on EVERY startup when
# GEOSERVER_ADMIN_USER/GEOSERVER_ADMIN_PASSWORD env vars are set, triggering an unnecessary
# GeoServer webapp reload. To avoid this, we unset the env vars when users.xml already exists
# (i.e. credentials were already applied on a previous start). To change the admin password
# later, delete users.xml and restart: see README.md for details.

SECURITY_DIR="${GEOSERVER_DATA_DIR}/security"
DEFAULT_SECURITY_DIR="${CATALINA_HOME}/webapps/geoserver/data/security"

# Check if users.xml already exists BEFORE copying defaults.
# This determines whether this is a first start (needs credential setup) or a subsequent start.
USERS_XML_EXISTS=false
if [ -f "${SECURITY_DIR}/usergroup/default/users.xml" ]; then
    USERS_XML_EXISTS=true
fi

# Only proceed if the security directory exists (tracked config) but is missing secret files
if [ -d "${SECURITY_DIR}" ]; then
    # Copy master password file if missing
    if [ ! -f "${SECURITY_DIR}/masterpw/default/passwd" ]; then
        echo "Initializing missing master password file from defaults..."
        mkdir -p "${SECURITY_DIR}/masterpw/default"
        cp "${DEFAULT_SECURITY_DIR}/masterpw/default/passwd" "${SECURITY_DIR}/masterpw/default/passwd"
    fi

    # Copy users.xml if missing (contains default admin credentials)
    if [ ! -f "${SECURITY_DIR}/usergroup/default/users.xml" ]; then
        echo "Initializing missing users.xml from defaults..."
        mkdir -p "${SECURITY_DIR}/usergroup/default"
        cp "${DEFAULT_SECURITY_DIR}/usergroup/default/users.xml" "${SECURITY_DIR}/usergroup/default/users.xml"
    fi
fi

# Skip credential update on subsequent starts to avoid unnecessary webapp reload.
# On first start users.xml did not exist so env vars are kept and update_credentials.sh runs.
# On subsequent starts users.xml already existed so we unset the env vars to skip the update.
if [ "${USERS_XML_EXISTS}" = true ]; then
    unset GEOSERVER_ADMIN_USER
    unset GEOSERVER_ADMIN_PASSWORD
else
    if [ -z "${GEOSERVER_ADMIN_USER}" ] || [ -z "${GEOSERVER_ADMIN_PASSWORD}" ]; then
        echo "WARNING: This is a first start (users.xml does not exist) but GEOSERVER_ADMIN_USER and/or GEOSERVER_ADMIN_PASSWORD environment variables are not set."
        echo "GeoServer will use default credentials (admin/geoserver). Set these variables in your .env file to configure custom admin credentials."
    fi
fi

# Delegate to the original startup script which handles GEOSERVER_ADMIN_USER/PASSWORD
# via /opt/handle_geoserver_admin_credentials.sh -> /opt/update_credentials.sh
exec /opt/startup.sh "$@"
