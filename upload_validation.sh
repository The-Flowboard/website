#!/usr/bin/expect -f

# SFTP upload script for validation framework
set timeout 30

spawn sftp ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "sftp>"
send "cd /var/www/html\r"

expect "sftp>"
send "put php/input_validator.php php/\r"

expect "sftp>"
send "put js/form-validator.js js/\r"

expect "sftp>"
send "put js/contact-form-init.js js/\r"

expect "sftp>"
send "put php/contact_handler.php php/\r"

expect "sftp>"
send "put contact.html ./\r"

expect "sftp>"
send "bye\r"

expect eof
