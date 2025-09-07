# In-App Purchase Validation with App Store Server API

This document explains how to use the new App Store Server API for validating in-app purchases, which replaces the deprecated `verifyReceipt` endpoint.

## Overview

Apple has deprecated the `verifyReceipt` endpoint and introduced the App Store Server API, which provides better performance, more detailed information, and real-time updates through server notifications.

## Key Changes

- **Deprecated**: `verifyReceipt` endpoint
- **New**: App Store Server API with transaction-based validation
- **Authentication**: Uses the same JWT tokens as App Store Connect API
- **Benefits**: Better performance, detailed transaction information, real-time server notifications

## Quick Start

### 1. Setup

```php
<?php
require_once 'vendor/autoload.php';

use AppleClient;
use AppleService_AppStoreServer;
use Cantie\AppStoreConnect\Validator\InAppPurchaseValidator;

// Initialize client
$client = new AppleClient();
$client->setApiKey('path/to/your/private-key.p8');
$client->setKeyIdentifier('YOUR_KEY_IDENTIFIER');
$client->setIssuerId('YOUR_ISSUER_ID');

// Create App Store Server service
$appStoreServer = new AppleService_AppStoreServer($client);

// Create validator
$validator = new InAppPurchaseValidator($appStoreServer, 'com.yourapp.bundleid');
```

### 2. Validate a Single Transaction

```php
// Replace with actual transaction ID from your app
$transactionId = '1000000123456789';

try {
    $transaction = $validator->validateTransaction($transactionId);
    
    if ($transaction && $validator->isTransactionValid($transaction)) {
        echo "✅ Valid transaction for product: " . $transaction['productId'];
        // Grant access to content/features
    } else {
        echo "❌ Invalid or refunded transaction";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### 3. Get Transaction History

```php
// Get all transactions for a customer
$originalTransactionId = '1000000123456789';

$history = $validator->getTransactionHistory($originalTransactionId, [
    'startDate' => strtotime('-30 days') * 1000, // Optional: 30 days ago
    'productIds' => ['com.yourapp.premium', 'com.yourapp.coins'] // Optional: filter by products
]);

foreach ($history['transactions'] as $transaction) {
    if ($validator->isTransactionValid($transaction)) {
        echo "Valid purchase: " . $transaction['productId'];
    }
}
```

### 4. Check Subscription Status

```php
// For subscription products
$subscriptionInfo = $validator->getSubscriptionStatuses($originalTransactionId);

if ($subscriptionInfo) {
    foreach ($subscriptionInfo['subscriptionStatuses'] as $subscriptionGroup) {
        if ($validator->isSubscriptionActive($subscriptionGroup)) {
            echo "✅ Active subscription";
        } else {
            echo "❌ Inactive subscription";
        }
    }
}
```

## Server Notifications V2

Handle real-time updates from Apple using Server Notifications V2:

### Setup Webhook Handler

```php
<?php
// webhook/app-store-notifications.php
require_once '../vendor/autoload.php';

use Cantie\AppStoreConnect\Validator\ServerNotificationValidator;

$validator = new ServerNotificationValidator('com.yourapp.bundleid');

// Get notification data
$input = file_get_contents('php://input');
$notification = json_decode($input, true);

// Validate notification
$validatedNotification = $validator->validateNotification($notification['signedPayload']);
if (!$validatedNotification) {
    http_response_code(400);
    exit('Invalid notification');
}

// Process notification
$processedNotification = $validator->processNotification($validatedNotification);

// Handle different notification types
if ($validator->isSuccessfulPurchase($processedNotification)) {
    // Handle successful purchase
    $transactionInfo = $processedNotification['transactionInfo'];
    // Update your database, grant access, etc.
    
} elseif ($validator->isRefund($processedNotification)) {
    // Handle refund
    // Revoke access, update database, etc.
    
} elseif ($validator->isSubscriptionCancellation($processedNotification)) {
    // Handle subscription cancellation
    // Update subscription status, etc.
}

// Always respond with 200 OK
http_response_code(200);
echo 'OK';
```

## Migration from verifyReceipt

### Old Way (Deprecated)

```php
// DON'T USE - This is deprecated
$receiptData = $_POST['receipt_data'];
$response = file_get_contents('https://buy.itunes.apple.com/verifyReceipt', false, 
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['receipt-data' => $receiptData, 'password' => $password])
        ]
    ])
);
```

### New Way

```php
// Extract transaction ID from your app (StoreKit 2 provides this)
$transactionId = $storeKitTransaction->id; // From StoreKit 2

// Validate using new API
$transaction = $validator->validateTransaction($transactionId);
```

## API Reference

### InAppPurchaseValidator

#### Methods

- `validateTransaction($transactionId)` - Validate a single transaction
- `getTransactionHistory($originalTransactionId, $options = [])` - Get customer's transaction history
- `getSubscriptionStatuses($originalTransactionId, $status = [])` - Get subscription statuses
- `isTransactionValid($transaction)` - Check if transaction is valid (not refunded)
- `isSubscriptionActive($subscriptionStatus)` - Check if subscription is active

#### Parameters

**getTransactionHistory() options:**
- `startDate` - Start date in milliseconds
- `endDate` - End date in milliseconds
- `productIds` - Array of product IDs to filter
- `productTypes` - Array of product types
- `sort` - Sort order ('ASCENDING' or 'DESCENDING')

### ServerNotificationValidator

#### Methods

- `validateNotification($signedPayload)` - Validate incoming notification
- `processNotification($notification)` - Process and decode notification data
- `isSuccessfulPurchase($processedNotification)` - Check if notification indicates successful purchase
- `isRefund($processedNotification)` - Check if notification indicates refund
- `isSubscriptionCancellation($processedNotification)` - Check if notification indicates cancellation
- `getNotificationDescription($processedNotification)` - Get human-readable description

## Notification Types

Common notification types you'll receive:

- `INITIAL_BUY` - Customer made initial purchase
- `DID_RENEW` - Subscription renewed successfully
- `DID_FAIL_TO_RENEW` - Subscription failed to renew
- `EXPIRED` - Subscription expired
- `REFUND` - Transaction was refunded
- `REVOKE` - Family sharing purchase was revoked

## Error Handling

```php
try {
    $transaction = $validator->validateTransaction($transactionId);
} catch (\Cantie\AppStoreConnect\Exception $e) {
    // Handle API errors
    error_log('Validation failed: ' . $e->getMessage());
    
    // Check for specific error types
    if (strpos($e->getMessage(), 'not found') !== false) {
        // Transaction not found
    } elseif (strpos($e->getMessage(), 'unauthorized') !== false) {
        // Authentication error
    }
}
```

## Best Practices

1. **Store Transaction IDs**: Save transaction IDs from StoreKit 2 in your app
2. **Use Server Notifications**: Implement webhook handler for real-time updates
3. **Validate Bundle ID**: Always verify transactions belong to your app
4. **Handle Errors Gracefully**: Implement proper error handling and logging
5. **Test Thoroughly**: Use sandbox environment for testing
6. **Monitor Performance**: The new API is faster but monitor response times

## Testing

Use the test notification endpoint to verify your webhook:

```php
$testResponse = $appStoreServer->notifications->requestTestNotification();
$testToken = $testResponse->getTestNotificationToken();

// Check test notification status
$statusResponse = $appStoreServer->notifications->getTestNotificationStatus($testToken);
```

## Configuration in App Store Connect

1. Go to App Store Connect
2. Navigate to your app > App Information
3. Scroll to "App Store Server Notifications"
4. Enter your webhook URL: `https://yourserver.com/webhook/app-store-notifications`
5. Set notification version to "Version 2"

## Environment URLs

- **Production**: `https://api.storekit.itunes.apple.com/`
- **Sandbox**: `https://api.storekit-sandbox.itunes.apple.com/`

The service automatically uses the production URL. For sandbox testing, configure the client with the sandbox URL.

## Troubleshooting

### Common Issues

1. **Authentication Errors**: Verify your API key, key identifier, and issuer ID
2. **Bundle ID Mismatch**: Ensure transaction belongs to your app
3. **Transaction Not Found**: Transaction ID might be invalid or from different environment
4. **Webhook Not Receiving**: Check URL configuration in App Store Connect

### Debugging

Enable logging to debug issues:

```php
$client->setLogger(new \Monolog\Logger('appstore'));
```

For more information, see Apple's [App Store Server API documentation](https://developer.apple.com/documentation/appstoreserverapi).