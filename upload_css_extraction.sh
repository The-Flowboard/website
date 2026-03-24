#!/usr/bin/expect -f

# Upload CSS extraction changes
set timeout 60

spawn sftp ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "sftp>"
send "cd /tmp\r"

# Upload CSS file
expect "sftp>"
send "put css/main-styles.css\r"

# Upload all HTML files
expect "sftp>"
send "put index.html\r"

expect "sftp>"
send "put about.html\r"

expect "sftp>"
send "put services.html\r"

expect "sftp>"
send "put blog.html\r"

expect "sftp>"
send "put courses.html\r"

expect "sftp>"
send "put contact.html\r"

expect "sftp>"
send "put assessment.html\r"

expect "sftp>"
send "put privacy-policy.html\r"

expect "sftp>"
send "put terms-of-service.html\r"

expect "sftp>"
send "bye\r"

expect eof
