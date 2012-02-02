<?php

/* 
 * Part of my honeypot.jayscott.co.uk project. 
 * Jay Scott <jay@jayscott.co.uk>
 * 
 * Script that I ran on the cron to dump the .log files into a directory for
 * ajax-term to read. 
 * 
 */

# Change to your information. 
$db = mysql_pconnect("localhost","kippo","your-password");
mysql_select_db("kippo",$db);

# I found that if the log was < 85 there was normally no command issued. 
$QUERY_TTY = mysql_query("SELECT id, session FROM ttylog WHERE LENGTH(ttylog) > 85");

if($QUERY_TTY)
	echo "Query Complete\n";
else 
	echo "Query Failed\n";

$num_rows = mysql_num_rows($QUERY_TTY);

echo "Rows = $num_rows \n";
echo mysql_error(); 

# Change location-to-store-logs to where you want to store the Kippo log files e.g. /var/opt/webroot/logs
while($tty_row = mysql_fetch_array($QUERY_TTY)) {
  mysql_query("SELECT ttylog FROM ttylog WHERE id=" . $tty_row['id'] . " into dumpfile 'location-to-store-logs" . $tty_row['session'] . ".log'"); 
    if($tty_row){
      echo " Command is successful \n";
      echo "ttylog = " . $tty_row['id'] . "\n";
	  } 
    else
      echo " Command not successful \n";
}

?>
