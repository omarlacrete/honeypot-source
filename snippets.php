<?

/*
 * Part of my honeypot.jayscott.co.uk project. 
 * Jay Scott <jay@jayscott.co.uk>
 * 
 * Various code snippets I used in pages through-out the project, didn't think
 * there was much point in displaying all of the HTML etc.
 */


/* === Get unique Malware links === */

$QUERY_DOWNLOAD = mysql_query("SELECT input.input, input.timestamp, sessions.ip 
                               FROM input INNER JOIN sessions 
                               ON input.session = sessions.id 
                               WHERE input.input LIKE '%wget%'
                               GROUP BY input.input 
                               ORDER BY input.timestamp DESC ");

while ($DOWNLOAD_ROW = mysql_fetch_array($QUERY_DOWNLOAD)) {
  if (strlen($DOWNLOAD_ROW['input']) > 8) {
    
    $Date = strtotime($DOWNLOAD_ROW["timestamp"]);
    $myDate = date('D jS M, G:i:s', $Date);
    
    $URL = htmlspecialchars($DOWNLOAD_ROW['input']);
  
    echo "<tr><td>$myDate</td>
          <td>" . substr($URL, 5) . "</td></tr>";
  }
}



/* === Get unique passwords === */

$sql_date = mysql_real_escape_string($_GET['date']);

if ($sql_date == 'all') {
  $previous_date = "2011-02-01"; /* date I started logging via sql */
} else if ($sql_date == 'week') {
  $previous_date = date("Y-m-d", strtotime("-7 day"));
} else if ($sql_date == 'month') {
  $previous_date = date("Y-m-d", strtotime("-30 day"));
} else {
  $previous_date = date("Y-m-d", strtotime("-1 day"));
}

/* simply change password to username for username stats */
$query_passwords = mysql_query("SELECT COUNT(password) AS PCOUNT, password 
                                FROM auth WHERE password <> ''
                                AND timestamp >= '$previous_date' 
                                GROUP BY password 
                                ORDER BY PCOUNT DESC LIMIT 20");


/* === Showing information on the attack === */

$QUERY_CLIENT = mysql_query("SELECT version FROM clients 
                             WHERE id = '$CLIENT'
                             LIMIT 1");
                               
$CLIENT_SEARCH = strtolower($ROWS_CLIENT['version']);
  
  if (strpos($CLIENT_SEARCH, "putty"))
    echo "<b>Connected Manually</b> ";
  else if (strpos($CLIENT_SEARCH, "libssh"))
    echo "<b>Used a C scanner</b> ";
  else if (strpos($CLIENT_SEARCH, "winscp"))
    echo "<b>Used WinSCP</b> ";
  else if (strpos($CLIENT_SEARCH, "openssh"))
    echo "<b>Connected Manually</b> ";
  else if (strpos($CLIENT_SEARCH, "nmap"))
    echo "<b>NMap Scan</b> ";



?>
