#!/usr/bin/expect -f

# Move validation files from /tmp to /var/www/html
set timeout 30

spawn ssh ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "$ "
send "sudo mv /tmp/input_validator.php /var/www/html/php/\r"

expect "$ "
send "sudo mv /tmp/form-validator.js /var/www/html/js/\r"

expect "$ "
send "sudo mv /tmp/contact-form-init.js /var/www/html/js/\r"

expect "$ "
send "sudo mv /tmp/contact_handler.php /var/www/html/php/\r"

expect "$ "
send "sudo mv /tmp/contact.html /var/www/html/\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/php/input_validator.php\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/js/form-validator.js\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/js/contact-form-init.js\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/php/contact_handler.php\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/contact.html\r"

expect "$ "
send "sudo chmod 644 /var/www/html/php/input_validator.php\r"

expect "$ "
send "sudo chmod 644 /var/www/html/js/form-validator.js\r"

expect "$ "
send "sudo chmod 644 /var/www/html/js/contact-form-init.js\r"

expect "$ "
send "sudo chmod 644 /var/www/html/php/contact_handler.php\r"

expect "$ "
send "sudo chmod 644 /var/www/html/contact.html\r"

expect "$ "
send "echo 'Validation framework deployed successfully!'\r"

expect "$ "
send "exit\r"

expect eof
