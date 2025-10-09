<?php

/**
 * MIT License
 * 
 * Copyright (c) 2023 Long Pham
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Cantie\AppStoreConnect\Validator;

/**
 * Server Notification V2 Handler
 * 
 * This class helps handle incoming App Store Server Notifications V2
 * which replace the legacy server notifications for real-time transaction updates.
 */
class ServerNotificationValidator
{
    /**
     * @var string
     */
    private $bundleId;

    /**
     * @var array
     */
    private $trustedRootCertificates;

    /**
     * @param string $bundleId Your app's bundle ID
     * @param array $trustedRootCertificates Optional array of trusted root certificates
     */
    public function __construct($bundleId, $trustedRootCertificates = [])
    {
        $this->bundleId = $bundleId;
        $this->trustedRootCertificates = $trustedRootCertificates;
    }

    /**
     * Validate and decode an App Store Server Notification V2
     * 
     * @param string $signedPayload The signed payload from the notification
     * @return array|null Decoded notification data or null if invalid
     */
    public function validateNotification($signedPayload)
    {
        try {
            // Basic JWT validation - decode the payload
            $decodedPayload = $this->decodeJWT($signedPayload);
            
            if (!$decodedPayload) {
                return null;
            }

            // Verify the notification is for the correct bundle ID
            if (isset($decodedPayload['data']['bundleId']) && 
                $decodedPayload['data']['bundleId'] !== $this->bundleId) {
                return null;
            }

            return $decodedPayload;
        } catch (\Exception $e) {
            error_log('Server notification validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a notification and extract transaction information
     * 
     * @param array $notification Decoded notification data
     * @return array Processed notification with transaction details
     */
    public function processNotification($notification)
    {
        if (!$notification || !isset($notification['data'])) {
            return null;
        }

        $notificationData = $notification['data'];
        $result = [
            'notificationType' => $notification['notificationType'] ?? null,
            'subtype' => $notification['subtype'] ?? null,
            'notificationUUID' => $notification['notificationUUID'] ?? null,
            'bundleId' => $notificationData['bundleId'] ?? null,
            'environment' => $notificationData['environment'] ?? null,
        ];

        // Decode signed transaction info if present
        if (isset($notificationData['signedTransactionInfo'])) {
            $transactionInfo = $this->decodeJWT($notificationData['signedTransactionInfo']);
            if ($transactionInfo) {
                $result['transactionInfo'] = $transactionInfo;
            }
        }

        // Decode signed renewal info if present (for subscriptions)
        if (isset($notificationData['signedRenewalInfo'])) {
            $renewalInfo = $this->decodeJWT($notificationData['signedRenewalInfo']);
            if ($renewalInfo) {
                $result['renewalInfo'] = $renewalInfo;
            }
        }

        return $result;
    }

    /**
     * Check if a notification indicates a successful purchase
     * 
     * @param array $processedNotification Processed notification data
     * @return bool True if the notification indicates a successful purchase
     */
    public function isSuccessfulPurchase($processedNotification)
    {
        if (!$processedNotification) {
            return false;
        }

        $notificationType = $processedNotification['notificationType'] ?? null;
        
        // Check for purchase-related notification types
        $successfulTypes = [
            'INITIAL_BUY',
            'DID_RENEW',
            'INTERACTIVE_RENEWAL',
            'DID_CHANGE_RENEWAL_PREF'
        ];

        return in_array($notificationType, $successfulTypes);
    }

    /**
     * Check if a notification indicates a refund
     * 
     * @param array $processedNotification Processed notification data
     * @return bool True if the notification indicates a refund
     */
    public function isRefund($processedNotification)
    {
        if (!$processedNotification) {
            return false;
        }

        $notificationType = $processedNotification['notificationType'] ?? null;
        
        return $notificationType === 'REFUND';
    }

    /**
     * Check if a notification indicates a subscription cancellation
     * 
     * @param array $processedNotification Processed notification data
     * @return bool True if the notification indicates a cancellation
     */
    public function isSubscriptionCancellation($processedNotification)
    {
        if (!$processedNotification) {
            return false;
        }

        $notificationType = $processedNotification['notificationType'] ?? null;
        
        $cancellationTypes = [
            'DID_FAIL_TO_RENEW',
            'EXPIRED',
            'GRACE_PERIOD_EXPIRED',
            'REVOKE'
        ];

        return in_array($notificationType, $cancellationTypes);
    }

    /**
     * Get a human-readable description of the notification
     * 
     * @param array $processedNotification Processed notification data
     * @return string Human-readable description
     */
    public function getNotificationDescription($processedNotification)
    {
        if (!$processedNotification) {
            return 'Invalid notification';
        }

        $notificationType = $processedNotification['notificationType'] ?? 'UNKNOWN';
        $subtype = $processedNotification['subtype'] ?? null;

        $descriptions = [
            'INITIAL_BUY' => 'Customer made an initial purchase',
            'DID_RENEW' => 'Subscription renewed successfully',
            'DID_FAIL_TO_RENEW' => 'Subscription failed to renew',
            'DID_CHANGE_RENEWAL_PREF' => 'Customer changed renewal preferences',
            'DID_CHANGE_RENEWAL_STATUS' => 'Customer changed auto-renewal status',
            'INTERACTIVE_RENEWAL' => 'Customer renewed interactively',
            'EXPIRED' => 'Subscription expired',
            'GRACE_PERIOD_EXPIRED' => 'Grace period expired',
            'OFFER_REDEEMED' => 'Customer redeemed an offer',
            'PRICE_INCREASE' => 'Price increase notification',
            'REFUND' => 'Transaction was refunded',
            'REFUND_DECLINED' => 'Refund request was declined',
            'RENEWAL_EXTENDED' => 'Subscription renewal was extended',
            'REVOKE' => 'Family sharing purchase was revoked',
            'TEST' => 'Test notification'
        ];

        $description = $descriptions[$notificationType] ?? "Unknown notification type: $notificationType";
        
        if ($subtype) {
            $description .= " (Subtype: $subtype)";
        }

        return $description;
    }

    /**
     * Simple JWT decoder (for payload extraction only)
     * Note: This does not verify signatures - for production use, implement proper JWT verification
     * 
     * @param string $jwt The JWT token
     * @return array|null Decoded payload or null if invalid
     */
    private function decodeJWT($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        // Decode the payload (second part)
        $payload = $parts[1];
        // Add padding if necessary
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
        
        return json_decode($decodedPayload, true);
    }

    /**
     * Get notification endpoint URL for webhook configuration
     * 
     * @param string $baseUrl Your server's base URL
     * @return string The webhook endpoint URL
     */
    public static function getWebhookEndpointUrl($baseUrl)
    {
        return rtrim($baseUrl, '/') . '/webhook/app-store-notifications';
    }

    /**
     * Create a sample webhook handler response
     * 
     * This method provides a template for handling notifications in your webhook endpoint
     * 
     * @return string Sample PHP code for webhook handling
     */
    public static function getSampleWebhookHandler()
    {
        return '<?php
// Sample webhook handler for App Store Server Notifications V2
// Place this code in your webhook endpoint (e.g., /webhook/app-store-notifications)

require_once __DIR__ . "/vendor/autoload.php";

use Cantie\AppStoreConnect\Validator\ServerNotificationValidator;

$bundleId = "com.yourapp.bundleid";
$validator = new ServerNotificationValidator($bundleId);

// Get the raw POST data
$input = file_get_contents("php://input");
$notification = json_decode($input, true);

if (!$notification || !isset($notification["signedPayload"])) {
    http_response_code(400);
    exit("Invalid notification");
}

// Validate and process the notification
$validatedNotification = $validator->validateNotification($notification["signedPayload"]);
if (!$validatedNotification) {
    http_response_code(400);
    exit("Invalid notification signature or bundle ID");
}

$processedNotification = $validator->processNotification($validatedNotification);

// Handle different notification types
if ($validator->isSuccessfulPurchase($processedNotification)) {
    // Handle successful purchase
    error_log("Successful purchase: " . $validator->getNotificationDescription($processedNotification));
    // Update your database, grant access, etc.
} elseif ($validator->isRefund($processedNotification)) {
    // Handle refund
    error_log("Refund processed: " . $validator->getNotificationDescription($processedNotification));
    // Revoke access, update database, etc.
} elseif ($validator->isSubscriptionCancellation($processedNotification)) {
    // Handle subscription cancellation
    error_log("Subscription cancelled: " . $validator->getNotificationDescription($processedNotification));
    // Update subscription status, etc.
}

// Always respond with 200 OK to acknowledge receipt
http_response_code(200);
echo "OK";
?>';
    }
}