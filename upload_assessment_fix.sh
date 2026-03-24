#!/usr/bin/expect -f

# Upload assessment.js fix
set timeout 30

spawn sftp ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "sftp>"
send "cd /tmp\r"

expect "sftp>"
send "put js/assessment.js\r"

expect "sftp>"
send "bye\r"

expect eof
