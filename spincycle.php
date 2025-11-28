#!/usr/bin/env php
<?php

		$enabled = false;
		$event_log = "/home/steve/Videos/Rip-o-Matic/event.log";


	error_reporting(E_ALL & ~E_DEPRECATED);

	require_once 'Console/CommandLine.php';

	$parser = new Console_CommandLine();
	$parser->description = "Spin Cycle DVDs";
	$parser->addArgument('device', array('optional' => false, 'multiple' => false));
	$parser->addOption('laundry_basket', array(
		'short_name' => '-b',
		'description' => 'Location of Laundry Basket',
		'action' => 'StoreString',
		'default' => '/home/steve/Media/Laundry-Basket',
	));
	$parser->addOption('force', array(
		'short_name' => '-f',
		'description' => 'Skip checking if enabled',
		'action' => 'StoreTrue',
		'default' => false,
	));
	$parser->addOption('dry_run', array(
		'short_name' => '-n',
		'description' => 'Do a dry run',
		'action' => 'StoreTrue',
		'default' => false,
	));

	try { $result = $parser->parse(); }
	catch(PEAR_Exception $e) {
		echo "Invalid options passed, try --help instead\n";
		exit(1);
	}

	extract($result->args);
	extract($result->options);

	if($force)
		$enabled = true;

	if(!$enabled)
		exit;

	$device = realpath($device);

	function procs_running($device) {

		$device = escapeshellarg(realpath($device));
		$cmd = "pgrep -af $device | grep -v pgrep | grep -v spincycle";
		exec($cmd, $arr, $retval);

		return $arr;

	}

	function dart_running($device) {

		$cmd = "pgrep -af dart";
		exec($cmd, $arr, $retval);

		foreach($arr as $str) {
			if(strstr($str, $device)) {
				return true;
			}
		}

		// If dart is running *at all* and it is using default of /dev/sr0, then there
		// could be no entry on the command-line, so just exit to be safe
		if(count($arr))
			return true;

		return false;

	}

	$procs_running = procs_running($device);

	if(count($procs_running) && !$dry_run) {
		foreach($procs_running as $str)
			echo "Running: $str\n";
		exit;
	}

	$event_log = "/home/steve/Videos/Rip-o-Matic/event.log";

	$date_format = "+%Y-%m-%d %r";

	$bn = basename($device);

	$dvd_backup_log = "/home/steve/Videos/Rip-o-Matic/spincycle.$bn.log";

	$disc_type = trim(shell_exec("disc_type $device"));

	var_dump($disc_type);

	if($disc_type != 'dvd')
		exit;

	$label = trim(shell_exec("udevadm info $device | grep 'ID_FS_LABEL=' | cut -d = -f 2-"));

	var_dump($label);

	$cmd = "dvd_drive_status $device";

	exec($cmd, $arr, $retval);

	if($retval != 4)
		exit;

	sleep(2);

	shell_exec("eject -t $device");

	// FIXME I need dart --iso-filename to return blank one with dvd id  0.000.1234.NSIX.iso
	$iso_directory = trim(shell_exec("/home/steve/bin/dart --iso-filename $device"));
		echo "FIXME - NEED dart --iso-filename to write to dvd id\n";
		exit;

	echo getcwd()."\n";
	$bool = chdir($laundry_basket);
	if(!$bool)
		exit;
	echo getcwd()."\n";

	if(file_exists($iso_directory)) {
		shell_exec("eject $device");
		exit;
	}

	$rip_directory = basename($iso_directory, '.iso').'R1p';

	if(file_exists($rip_directory))
		exit;

	$procs_running = procs_running($device);

	if(count($procs_running)) {
		foreach($procs_running as $str)
			echo "Running: $str\n";
		exit;
	}

	$cmd = "dart --backup $device";

	if($dry_run) {
		echo "$cmd\n";
		exit;
	}

	passthru("dart --backup $device | tee -a $dvd_backup_log", $retval);

	shell_exec("eject $device");
