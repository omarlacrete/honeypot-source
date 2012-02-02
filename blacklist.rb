#!/usr/bin/ruby


# Part of my honeypot.jayscott.co.uk project. 
# Jay Scott <jay@jayscott.co.uk>


require 'rubygems'
require 'mysql'  

# Set the dates we want to start at
date = Time.new

# Change pass to your password. 
con_kippo = Mysql.new('localhost', 'kippo', 'pass', 'kippo')

rs_list = con_kippo.query("SELECT ip 
                FROM sessions
                WHERE starttime LIKE '2011-#{date.month}%'
                GROUP BY ip 
                ORDER BY ip")

ip_list = Array.new

while row = rs_list.fetch_row do
    ip_list.push row[0]
end

rs_list.free

# You may want to define the absolute path in the following code blocks. 
File.open('ip-list.txt', 'w') do |f2|
  ip_list.each do|ip|
    f2.puts ip
  end
end

File.open('ip-list-iptables.txt', 'w') do |f2|
  ip_list.each do|ip|
    f2.puts "iptables -A INPUT -s #{ip} -j LOG --log-prefix \"Blocked: JayScott-Honeypot \""
    f2.puts "iptables -A INPUT -s #{ip} -j DROP"
  end
end

File.open('ip-list-cisco.txt', 'w') do |f2|
  ip_list.each do|ip|
    f2.puts "access-list 1 deny host #{ip}"
  end
  f2.puts "access-list 1 permit any"
end


con_kippo.close 
