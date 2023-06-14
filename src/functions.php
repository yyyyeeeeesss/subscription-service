<?php

declare(strict_types=1);

// Stub for the check_email function
function check_email(string $email): bool {
    log_message('Check email', [
        'email' => $email
    ]);
    // Here should be the real logic of email checking
    return true;
}

// Function to save the result of email checking
function save_info(PDO $db, int $userId, bool $isValid, bool $isChecked): void {

    log_message('Save info', [
        'userId' => $userId,
    ]);
    $query = $db->prepare("
        UPDATE users
        SET is_valid = :isValid, is_checked = :isChecked
        WHERE id = :userId
    ");

    $query->bindParam(':isValid', $isValid, PDO::PARAM_INT);
    $query->bindParam(':isChecked', $isChecked, PDO::PARAM_BOOL);
    $query->bindParam(':userId', $userId, PDO::PARAM_INT);
    $query->execute();
}

// Stub for the send_email function
function send_email(string $from, string $to, string $text): bool {
    log_message('Send email', [
        'email' => $to
    ]);

    // Here should be the real logic of sending email
    return true;
}

// Checking the validity
function is_valid(bool $isChecked, bool $isValidFromDb, string $email): bool {
    if (!$isChecked) {
        return check_email($email);
    }

    return $isValidFromDb;
}

function log_message(string $message, array $context = [])
{
    print_r(sprintf('%s - %s', $message, print_r($context, true)));
}

// Sending email
function send(int $interval, PDO $db, int $startUserId, int $limitUserId) {

    // Cursor initialization
    $lastId = $startUserId;

    while (true) {
        // Getting subscriptions that expire in 1 or 3 days
        $query = $db->prepare("
            SELECT subscriptions.id, users.email, users.is_valid, users.is_checked, subscriptions.user_id, users.username
            FROM subscriptions
            LEFT JOIN users ON users.id = subscriptions.user_id
            WHERE subscriptions.id > :lastId
              AND subscriptions.id <= :limitId
              AND date(subscriptions.expired_at) = (CURDATE() +  INTERVAL :interval DAY)            
              AND (is_valid = true OR is_checked = false)
            ORDER BY id ASC
            LIMIT 1000
        ");

        $query->bindParam(':lastId', $lastId, PDO::PARAM_INT);
        $query->bindParam(':interval', $interval, PDO::PARAM_INT);
        $query->bindParam(':limitId', $limitUserId, PDO::PARAM_INT);
        $query->execute();
        $subscriptions = $query->fetchAll(PDO::FETCH_ASSOC);

        // If subscriptions are over, exit the loop
        if (!$subscriptions) {
            break;
        }

        // Processing each subscription
        foreach ($subscriptions as $subscription) {

            $isChecked = $subscription['is_checked'];
            $isValid = $subscription['is_valid'];

            try {
                $isValid = is_valid((bool) $isChecked, (bool) $isValid, $subscription['email']);

                // If the email has already been checked and is valid
                if ($isValid) {
                    send_email('your_email@example.com', $subscription['email'], "{$subscription['username']}, your subscription is expiring soon");
                }

            } catch (Exception $exception) {
                log_message('Got an error', [
                    'username' => $subscription['username'],
                    'message' => $exception->getMessage()
                ]);
            } finally {
                if ($subscription['is_checked'] !== $isValid || $subscription['is_valid'] !== $isChecked) {
                    // Saving the result
                    save_info($db, $subscription['user_id'], (bool) $isValid, (bool) $isChecked);
                }
            }

            // Updating the cursor
            $lastId = $subscription['id'];
        }
    }
}

/**
 * @param PDO $db
 * @param int $countParts
 * @param int $interval
 * @return int
 */
function run_senders(PDO $db, int $countParts, int $interval): int
{
    // Initialize the increment variable in the database
    $db->prepare('SET @increment=0')->execute();

    $query = $db->prepare("
        select tmp.user_id from (
            SELECT  subscriptions.user_id, (@increment:=@increment+1) AS increment
            FROM subscriptions
            LEFT JOIN users ON users.id = subscriptions.user_id
            WHERE date(subscriptions.expired_at) = (CURDATE() +  INTERVAL :interval DAY)            
              AND (is_valid = true OR is_checked = false)
            ORDER BY subscriptions.user_id ASC
            ) as tmp
        WHERE MOD(tmp.increment, :countParts) = 0;
    ");

    $query->bindParam(':interval', $interval, PDO::PARAM_INT);
    $query->bindParam(':countParts', $countParts, PDO::PARAM_INT);
    $query->execute();

    $idsResponse = $query->fetchAll(PDO::FETCH_ASSOC);

    // Initialize an array to hold the divided parts
    $parts = [];

    // If there are returned rows, divide them into parts
    if ($idsResponse) {
        // The first part starts from 0 to the first user_id
        $parts[] = ['start_user_id' => 0, 'limit_user_id' => $idsResponse[0]['user_id']];

        // For each pair of user_ids, create a new part starting from the first user_id and ending with the second
        for ($i = 0; $i < count($idsResponse) - 1; $i++) {
            $parts[] = ['start_user_id' => $idsResponse[$i]['user_id'], 'limit_user_id' => $idsResponse[$i + 1]['user_id']];
        }

        // The last part starts from the last user_id and goes to the maximum integer
        $parts[] = ['start_user_id' => $idsResponse[$i]['user_id'], 'limit_user_id' => PHP_INT_MAX];

        // For each part, run the email-sender.php script with the part's bounds as arguments
        foreach ($parts as $part) {
            $command = sprintf('php email-sender.php %d %d %d > /dev/null &', $interval, $part['start_user_id'], $part['limit_user_id']);
            log_message('Run command', ['command' => $command]);
            exec($command);
        }
    }
}
