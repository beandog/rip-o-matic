all:
	gcc -o dvd_bounce dvd_bounce.c

install:
	for x in bluray_encode_script confcat dvd_bounce dvd_encode_script jfin_link lsencodes lssync plex_link plex_sync tout volname; do doas ln -sf `realpath $$x` /usr/local/bin/; done
