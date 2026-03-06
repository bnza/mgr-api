#!/bin/bash
# Initialize missing GeoServer security files
# These files are not tracked in git (public repo) and must be generated per environment.
# GeoServer will regenerate geoserver.jceks and masterpw.digest on first startup
# if masterpw/default/passwd and usergroup/default/users.xml exist.

SECURITY_DIR="${GEOSERVER_DATA_DIR}/security"
DEFAULT_SECURITY_DIR="${CATALINA_HOME}/webapps/geoserver/data/security"

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

# Delegate to the original startup script which handles GEOSERVER_ADMIN_USER/PASSWORD
# via /opt/handle_geoserver_admin_credentials.sh -> /opt/update_credentials.sh
exec /opt/startup.sh "$@"
