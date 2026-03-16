ARG GEOSERVER_VERSION=2.28.1
FROM docker.osgeo.org/geoserver:${GEOSERVER_VERSION}

COPY init.sh /opt/init.sh
RUN chmod +x /opt/init.sh

ENTRYPOINT ["bash", "/opt/init.sh"]
