all:
	gcc -o dvd_bounce dvd_bounce.c

install:
	for x in bluray_encode_script confcat drip dvd_bounce dvd_encode_script lsencodes lshb lsservices lsports lssync lsinstalled onevent.trayclose tout volname fixperms; do doas ln -sf `realpath $$x` /usr/local/bin/; done
