#!/usr/bin/expect -f

# Move HTML files from /tmp to /var/www/html
set timeout 30

spawn ssh ubuntu@167.114.97.221

expect "password:"
send "quxqof-sYkzim-7xymva\r"

expect "$ "
send "sudo mv /tmp/index.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/about.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/assessment.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/blog.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/contact.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/courses.html /var/www/html/\r"

expect "$ "
send "sudo mv /tmp/services.html /var/www/html/\r"

expect "$ "
send "sudo chown www-data:www-data /var/www/html/*.html\r"

expect "$ "
send "sudo chmod 644 /var/www/html/*.html\r"

expect "$ "
send "echo 'Image dimensions fix deployed successfully!'\r"

expect "$ "
send "exit\r"

expect eof
