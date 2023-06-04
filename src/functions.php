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
function send(int $interval, PDO $db) {

    // Cursor initialization
    $lastId = 0;

    while (true) {
        // Getting subscriptions that expire in 1 or 3 days
        $query = $db->prepare("
            SELECT subscriptions.id, users.email, users.is_valid, users.is_checked, subscriptions.user_id, users.username
            FROM subscriptions
            LEFT JOIN users ON users.id = subscriptions.user_id
            WHERE subscriptions.id > :lastId
              AND date(subscriptions.expired_at) = (CURDATE() +  INTERVAL :interval DAY)            
              AND (is_valid = true OR is_checked = false)
            ORDER BY id ASC
            LIMIT 1000
        ");
        $query->bindParam(':lastId', $lastId, PDO::PARAM_INT);
        $query->bindParam(':interval', $interval, PDO::PARAM_INT);
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

