ARG GEOSERVER_VERSION=2.28.1
FROM docker.osgeo.org/geoserver:${GEOSERVER_VERSION}

COPY init-security.sh /opt/init-security.sh
RUN chmod +x /opt/init-security.sh

ENTRYPOINT ["bash", "/opt/init-security.sh"]
