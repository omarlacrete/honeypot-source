<?php

/*
 * 
 * Gets the IP address from the kippo DB and reports the IP for abuse if certain 
 * conditions are met. Then saves the information to a 'report' table for 
 * displaying information at a later date. 
 * 
 * I still have debugging echo statements etc floating about :p
 * 
 * 
 * report table - added to kippo database
 * 
 * CREATE TABLE IF NOT EXISTS `report` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `name` char(50) NOT NULL,
 * `ip` varchar(15) NOT NULL,
 * `contact` varchar(200) NOT NULL,
 * `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 * `replied` tinyint(1) NOT NULL DEFAULT '0',
 * `contacted` tinyint(1) NOT NULL DEFAULT '1',
 * `notes` text NOT NULL,
 * PRIMARY KEY (`id`)
 * );
 * 
 * 
 * Uses pears Mail script, this can be easily change to PHP's mail().  
 * 
 * pear install Mail
 * 
 */

error_reporting(0);

require_once "Mail.php";

function attackAttempts($id, $db)
{
  $result = mysql_query("SELECT COUNT(id) AS IPCOUNT FROM sessions WHERE ip ='" . $id . "'", $db) or die(mysql_error());
  if ($row = mysql_fetch_array($result))
    return (int) $row['IPCOUNT'];
}

function attackSuccessful($id, $db)
{
  $result = mysql_query("SELECT auth.session, auth.success FROM auth 
			                   INNER JOIN sessions ON auth.session = sessions.id 
                         WHERE auth.success=1 AND sessions.ip='$id'");

  $num_rows = (int) mysql_num_rows($result);
  return $num_rows;
}

/* Change to your Kippo DB password */
$db = mysql_pconnect("localhost", "kippo", "yourpassword");
mysql_select_db("kippo", $db);

$previous_date = date("Y-m-d", strtotime("-1 day"));

$QUERY_ATTACKS = mysql_query("SELECT auth.session, auth.`timestamp`, MAX(sessions.starttime) AS MAXTIME, MIN(sessions.starttime) AS MINTIME, 
                              sessions.ip, sessions.sensor 
                              FROM auth INNER JOIN sessions ON auth.session = sessions.id 
                              WHERE timestamp >= '$previous_date'
                              GROUP BY sessions.ip 
                              ORDER BY auth.id ");

while ($ROW_ATTACKER = mysql_fetch_array($QUERY_ATTACKS))
  {
    $IPADDRESS = $ROW_ATTACKER["ip"];
    $START     = $ROW_ATTACKER["MAXTIME"];
    $END       = $ROW_ATTACKER["MINTIME"];
    $SENSOR    = $ROW_ATTACKER["sensor"];
    $SESSION   = $ROW_ATTACKER["session"];
    $TIMESTAMP = $ROW_ATTACKER["timestamp"];

    /* Already in the DB? dont report again */
    $IP_EXISTS = mysql_query("SELECT contacted FROM report WHERE ip='$IPADDRESS'");
    
    if ($ROW_EXISTS = mysql_fetch_array($IP_EXISTS)) {
      continue;
    }

    echo "IP = $IPADDRESS\n";

    $attack_success = 0;
    $total_attacks = attackAttempts($IPADDRESS, $db);
    $attack_success = attackSuccessful($IPADDRESS, $db);
    
    if ($total_attacks > 10 )
      echo "More than 10 attempts ($total_attacks) ($attack_success)\n";
    else if ($attack_success > 0)
        echo "Attack Success ($total_attacks) ($attack_success)\n";
    else {
      echo "Less than 10 attempts ($total_attacks) ($attack_success)\n";
      continue;
    }
    
    $email = array();
    
    unset($f);
    /* Shouldn't need to sanitise the IP address */
    exec("whois $IPADDRESS ", $f);
    unset($tmpname);
    unset($output);
    
    foreach ($f as $output) {
      if (stripos($output, "netname:") === 0)
        $tmpname = explode(':',$output);
      else if (stripos($output, "owner:") === 0)
        $tmpname = explode(':',$output);
      
      preg_match('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $output, $matches);
      
      $email[] = strtolower($matches[0]);
      $email = array_filter($email);
    }
    
    $email = array_filter($email);
    $email = array_unique($email);
    
    $EMAILS = implode(" ",$email);
    $NAME   = trim($tmpname[1]);
    $email_parts = explode(" ", $EMAILS);

    foreach ($email_parts as $b_email) {

      $EMAIL_ABUSE = 0;
      $tmp_username = substr($b_email, 0, strpos($b_email, '@'));
      $tmp_username = strtolower($tmp_username);
      if ( $tmp_username == "abuse" || $tmp_username == "support") {
        $EMAIL_ABUSE = 1;
        $EMAILS = $b_email;
      }
    }

    if (empty($email)) {
      $INSERT_REPORT = mysql_query("INSERT INTO report (name, ip, contact, contacted, date) VALUES ('$NAME', '$IPADDRESS','', 0, '$TIMESTAMP')");
      continue;
    } else {
        $INSERT_REPORT = mysql_query("INSERT INTO report (name, ip, contact, date) VALUES ('$NAME', '$IPADDRESS','$EMAILS', '$TIMESTAMP')");
    }
        
    unset($to);
    $parts = explode(" ", $EMAILS);
    if (sizeof($parts) == 1)
      $to = rtrim($parts[0],'.');
    else {
      foreach ($parts as $send_cc) {
        $send_cc = rtrim($send_cc,'.');
        $to .= "$send_cc,";
      }
      
      $to = substr($to, 0, -1);
    }
    echo "TO = $to";

    /* Kippo stored the IP of the sensor as a name in the 'sensors' table, get 
     * the sensor ID and then identify IP.   
     */ 
    switch ($SENSOR)
    {
      case 1:
        $TARGET = "ip-removed";
        break;
      case 2:
        $TARGET = "ip-removed";
        break;
      case 3:
        $TARGET = "ip-removed";
        break;
      case 4:
        $TARGET = "ip-removed";
        break;
      case 5:
        $TARGET = "ip-removed";
        break;
      case 6:
        $TARGET = "ip-removed";
        break;
      case 7:
        $TARGET = "ip-removed";
        break;
      case 8:
        $TARGET = "ip-removed";
        break;
    }

    /* My SMTP information, change to yours or remove and add the default 
    PHP mail() command */
    $host = "ssl://smtp.gmail.com";
    $port = "465";
    $username = "";
    $password = '';

    $subject = "SSH attack from $IPADDRESS";
    $from    = 'jay@jayscott.co.uk';
    $headers = "From: $from \r\n" . "Reply-To: $from \r\n";

    $message = "To abuse/support,";
    
    if ($EMAIL_ABUSE = 0) {
        $message .= "
        
  Please note I could not find a abuse or support email address in an
  WHOIS lookup.";
    }

  $message .= "

  I run a honeypot network that reports any attacking IP address or
  successful logins from unauthorised IP address.

  The IP $IPADDRESS first gained access or attempted to access the
  honeypot on $START GMT against the IP address $TARGET.";

  $message .= "

  It maybe that $IPADDRESS has been compromised, is an active
  participant in a botnet or is being used as a SSH tunnel.

  You may wish to monitor the IP Address. You can view more details about
  the attack such as any more attacks carried out, amount of attacks and
  even watch the attack if they successfully logged in here:

  http://honeypot.jayscott.co.uk/ip/$IPADDRESS

  If you would like any advice or require further information please
  feel free to contact me, jay@jayscott.co.uk.

  Regards,
  Jay Scott";

    $headers = array ('From' => $from,
        'To' => $to,
        'Subject' => $subject);
    $smtp = Mail::factory('smtp',
        array ('host' => $host,
            'port' => $port,
            'auth' => true,
            'username' => $username,
            'password' => $password));

   $mail = $smtp->send($to, $headers, $message);

    if (PEAR::isError($mail)) {
        echo(" - " . $mail->getMessage() . "\n");
    } else {
        echo(" - Message sent\n\n");
    }
  }
?>
