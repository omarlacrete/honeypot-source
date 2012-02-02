#!/usr/bin/ruby
#
# Generates the current graph. 
# 
# gem install gruff
# gem install mysql
#

require 'rubygems'
require 'gruff'
require 'mysql'  

# Set the dates we want
date = Time.new
current_date = "#{date.year}-0#{date.month}"
string_month = Date::MONTHNAMES[date.month]

puts current_date
g = Gruff::Line.new("600x300")
g.title = "#{string_month} 2012 Attacks"

# Set the font options
g.font = 'LiberationMono-Regular.ttf'
g.marker_font_size = 12
g.legend_font_size = 12
g.title_font_size = 12

# Set the chart colours
@green    = '#339933'
@purple   = '#cc99cc'
@blue     = '#336699'
@yellow   = '#a21764'
@red      = '#ff0000'
@orange   = '#cf5910'
@black    = 'black'
@colors   = [@yellow, @blue, @green, @red, @black, @purple, @orange]
      
# Set the chart look
g.legend_box_size = 12
g.marker_count = 12
g.line_width = 1
g.dot_radius = 2
g.theme = {
  :colors => @colors,
  :marker_color => '#aea9a9',
  :font_color => 'black',
  :background_colors => 'white'
}

# Change the password to the kippo DB
con_kippo = Mysql.new('localhost', 'kippo', 'your-pass', 'kippo')

rs_sensors = con_kippo.query("SELECT id, ip FROM sensors")

while row_sensor = rs_sensors.fetch_row do

  rs_current = con_kippo.query("SELECT DISTINCT DATE(starttime) AS Date, COUNT(*) AS Total 
                                FROM sessions
                                WHERE starttime
                                LIKE '#{current_date}%'
                                AND sensor=#{row_sensor[0]}
                                GROUP BY Date 
                                ORDER BY Date")  

  attack_list = []
  total_attacks = 0

  puts "Current sensor is #{row_sensor[1]}"
  puts "Number of rows #{rs_current.num_rows}"
  while row = rs_current.fetch_row do
    #puts "Row 1 = #{row[1]} Row 2 = #{row[0]}"
    attack_list  << row[1].to_i
    total_attacks = total_attacks + row[1].to_i
  end

  #puts "Attack list is #{attack_list}"
  
  if total_attacks > 0 then 
    legend = "#{row_sensor[1]} (#{total_attacks})"
    g.data(legend, attack_list)
  end
  
  rs_current.free
  
end

x = 0
days_list = {}

while x < 31 do 
  days_list[x] = "#{x +1}"
  x =  x + 1
end
  
g.labels = days_list
g.write('current-month.png')

con_kippo.close 

