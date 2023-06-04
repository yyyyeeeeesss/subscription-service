###### Usage
`docker-compose up -d`

###### Run script
sending mails one day before the subscription expires:
``` 
docker-compose run php php email-sender.php 1
``` 
sending mails three day before the subscription expires:

```
docker-compose run php php email-sender.php 3
```
Scripts are designed to run every day. 
Add to cron jobs:

```
00 09 * * * php /var/www/email-sender.php 1
00 10 * * * php /var/www/email-sender.php 3
```