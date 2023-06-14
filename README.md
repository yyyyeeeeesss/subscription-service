###### Usage
`docker-compose up -d`

###### Run script
sending mails one day before the subscription expires:
``` 
docker-compose run php php email-sender.php 1 {startUserId} {lastUserId}
``` 
sending mails three day before the subscription expires:

```
docker-compose run php php email-sender.php 3 {startUserId} {lastUserId}
```
dispatcher.php can run multiple email-sender.php scripts depending on the passed parameter 'countParts'
```
php /var/www/dispatcher.php 0 10
```
Output:
```
Run command - Array
(
[command] => php email-sender.php 0 0 999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 999 1999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 1999 2999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 2999 3999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 3999 4999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 4999 5999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 5999 6999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 6999 7999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 7999 8999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 8999 9999 > /dev/null &
)
Run command - Array
(
[command] => php email-sender.php 0 9999 9223372036854775807 > /dev/null &
)
```
the parameter countParts is selected based on the number of records in the tables 

Scripts are designed to run every day. 
Add to cron jobs:

```
00 09 * * * php /var/www/dispatcher.php 1 10
00 10 * * * php /var/www/dispatcher.php 3 10
```