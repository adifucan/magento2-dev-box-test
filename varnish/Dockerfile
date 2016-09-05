FROM debian:jessie

RUN apt-get update && apt-get install -y apt-utils varnish vim

RUN echo 'vcl 4.0; backend default {.host = "web"; .port = "80";}' > /etc/varnish/default.vcl

CMD /usr/sbin/varnishd -P /run/varnishd.pid -a :6081 -F -T localhost:6082 -f /etc/varnish/default.vcl -S /etc/varnish/secret -s malloc,256m
