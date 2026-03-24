#!/usr/bin/expect -f

# SFTP upload script for validation framework (to /tmp)
set timeout 30

spawn sftp ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "sftp>"
send "cd /tmp\r"

expect "sftp>"
send "put php/input_validator.php\r"

expect "sftp>"
send "put js/form-validator.js\r"

expect "sftp>"
send "put js/contact-form-init.js\r"

expect "sftp>"
send "put php/contact_handler.php\r"

expect "sftp>"
send "put contact.html\r"

expect "sftp>"
send "bye\r"

expect eof
