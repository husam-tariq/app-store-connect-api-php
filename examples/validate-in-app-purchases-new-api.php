<?php

/**
 * Example: Validate In-App Purchases using the new App Store Server API
 * 
 * This example demonstrates how to use the new transaction validation approach
 * that replaces the deprecated verifyReceipt endpoint.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AppleClient;
use AppleService_AppStoreServer;
use Cantie\AppStoreConnect\Validator\InAppPurchaseValidator;

// Configuration
$apiKeyPath = 'path/to/your/private-key.p8';
$keyIdentifier = 'YOUR_KEY_IDENTIFIER';
$issuerId = 'YOUR_ISSUER_ID';
$bundleId = 'com.yourapp.bundleid';

// Initialize client with App Store Connect credentials
$client = new AppleClient();
$client->setApiKey($apiKeyPath);
$client->setKeyIdentifier($keyIdentifier);
$client->setIssuerId($issuerId);

// Create App Store Server service instance
$appStoreServer = new AppleService_AppStoreServer($client);

// Create validator instance
$validator = new InAppPurchaseValidator($appStoreServer, $bundleId);

echo "<h1>In-App Purchase Validation using New App Store Server API</h1>\n";
echo "<p><strong>Note:</strong> This replaces the deprecated verifyReceipt endpoint.</p>\n";

// Example 1: Validate a single transaction
echo "<h2>1. Validate Single Transaction</h2>\n";
try {
    $transactionId = 'YOUR_TRANSACTION_ID'; // Replace with actual transaction ID
    
    $transaction = $validator->validateTransaction($transactionId);
    
    if ($transaction) {
        echo "<h3>✅ Transaction Valid</h3>\n";
        echo "<pre>" . json_encode($transaction, JSON_PRETTY_PRINT) . "</pre>\n";
        
        // Check if transaction is valid (not refunded)
        if ($validator->isTransactionValid($transaction)) {
            echo "<p><strong>Status:</strong> Transaction is valid and not refunded</p>\n";
        } else {
            echo "<p><strong>Status:</strong> Transaction is invalid or refunded</p>\n";
        }
    } else {
        echo "<p>❌ Transaction not found or invalid bundle ID</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error validating transaction: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 2: Get transaction history
echo "<h2>2. Get Transaction History</h2>\n";
try {
    $originalTransactionId = 'YOUR_ORIGINAL_TRANSACTION_ID'; // Replace with actual original transaction ID
    
    $history = $validator->getTransactionHistory($originalTransactionId, [
        // Optional parameters
        // 'startDate' => strtotime('-30 days') * 1000, // 30 days ago in milliseconds
        // 'endDate' => time() * 1000, // Now in milliseconds
        // 'productIds' => ['com.yourapp.product1', 'com.yourapp.product2']
    ]);
    
    echo "<h3>Transaction History</h3>\n";
    echo "<p><strong>Environment:</strong> " . htmlspecialchars($history['environment']) . "</p>\n";
    echo "<p><strong>Has More:</strong> " . ($history['hasMore'] ? 'Yes' : 'No') . "</p>\n";
    echo "<p><strong>Transaction Count:</strong> " . count($history['transactions']) . "</p>\n";
    
    if (!empty($history['transactions'])) {
        echo "<h4>Transactions:</h4>\n";
        foreach ($history['transactions'] as $index => $transaction) {
            echo "<h5>Transaction " . ($index + 1) . ":</h5>\n";
            echo "<ul>\n";
            echo "<li><strong>Product ID:</strong> " . htmlspecialchars($transaction['productId'] ?? 'N/A') . "</li>\n";
            echo "<li><strong>Transaction ID:</strong> " . htmlspecialchars($transaction['transactionId'] ?? 'N/A') . "</li>\n";
            echo "<li><strong>Purchase Date:</strong> " . (isset($transaction['purchaseDate']) ? date('Y-m-d H:i:s', $transaction['purchaseDate'] / 1000) : 'N/A') . "</li>\n";
            echo "<li><strong>Valid:</strong> " . ($validator->isTransactionValid($transaction) ? 'Yes' : 'No') . "</li>\n";
            echo "</ul>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error getting transaction history: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 3: Get subscription statuses
echo "<h2>3. Get Subscription Statuses</h2>\n";
try {
    $originalTransactionId = 'YOUR_ORIGINAL_SUBSCRIPTION_TRANSACTION_ID'; // Replace with actual subscription transaction ID
    
    $subscriptionInfo = $validator->getSubscriptionStatuses($originalTransactionId);
    
    if ($subscriptionInfo) {
        echo "<h3>Subscription Information</h3>\n";
        echo "<p><strong>Environment:</strong> " . htmlspecialchars($subscriptionInfo['environment']) . "</p>\n";
        echo "<p><strong>Bundle ID:</strong> " . htmlspecialchars($subscriptionInfo['bundleId']) . "</p>\n";
        echo "<p><strong>App Apple ID:</strong> " . htmlspecialchars($subscriptionInfo['appAppleId']) . "</p>\n";
        
        foreach ($subscriptionInfo['subscriptionStatuses'] as $groupIndex => $subscriptionGroup) {
            echo "<h4>Subscription Group " . ($groupIndex + 1) . ":</h4>\n";
            echo "<p><strong>Group ID:</strong> " . htmlspecialchars($subscriptionGroup['subscriptionGroupIdentifier'] ?? 'N/A') . "</p>\n";
            
            foreach ($subscriptionGroup['lastTransactions'] as $transactionIndex => $transactionData) {
                echo "<h5>Transaction " . ($transactionIndex + 1) . ":</h5>\n";
                echo "<ul>\n";
                echo "<li><strong>Status:</strong> " . htmlspecialchars($transactionData['status'] ?? 'N/A') . "</li>\n";
                
                if (isset($transactionData['transactionInfo'])) {
                    $txInfo = $transactionData['transactionInfo'];
                    echo "<li><strong>Product ID:</strong> " . htmlspecialchars($txInfo['productId'] ?? 'N/A') . "</li>\n";
                    echo "<li><strong>Expires Date:</strong> " . (isset($txInfo['expiresDate']) ? date('Y-m-d H:i:s', $txInfo['expiresDate'] / 1000) : 'N/A') . "</li>\n";
                }
                
                echo "<li><strong>Active:</strong> " . ($validator->isSubscriptionActive($subscriptionGroup) ? 'Yes' : 'No') . "</li>\n";
                echo "</ul>\n";
            }
        }
    } else {
        echo "<p>❌ No subscription information found or invalid bundle ID</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error getting subscription statuses: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 4: Request test notification
echo "<h2>4. Request Test Notification</h2>\n";
try {
    $testResponse = $appStoreServer->notifications->requestTestNotification();
    echo "<p>✅ Test notification requested successfully</p>\n";
    echo "<p><strong>Test Notification Token:</strong> " . htmlspecialchars($testResponse->getTestNotificationToken()) . "</p>\n";
    
    // You can then check the status of this test notification
    sleep(2); // Wait a moment before checking status
    
    $statusResponse = $appStoreServer->notifications->getTestNotificationStatus($testResponse->getTestNotificationToken());
    echo "<p><strong>Send Attempts:</strong></p>\n";
    echo "<pre>" . json_encode($statusResponse->getSendAttempts(), JSON_PRETTY_PRINT) . "</pre>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error with test notification: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Migration guidance
echo "<h2>🔧 Migration from verifyReceipt</h2>\n";
echo "<div style='background-color: #f0f8ff; padding: 15px; border-left: 4px solid #0066cc;'>\n";
echo "<h3>Key Changes:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Deprecated:</strong> <code>verifyReceipt</code> endpoint</li>\n";
echo "<li><strong>New:</strong> App Store Server API with transaction IDs</li>\n";
echo "<li><strong>Authentication:</strong> Same JWT tokens used for App Store Connect API</li>\n";
echo "<li><strong>Benefits:</strong> Better performance, more detailed information, real-time updates</li>\n";
echo "</ul>\n";

echo "<h3>Migration Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Extract transaction IDs from your existing receipts</li>\n";
echo "<li>Use <code>validateTransaction()</code> for individual transactions</li>\n";
echo "<li>Use <code>getTransactionHistory()</code> for complete customer history</li>\n";
echo "<li>Use <code>getSubscriptionStatuses()</code> for subscription management</li>\n";
echo "<li>Implement server notifications V2 for real-time updates</li>\n";
echo "</ol>\n";

echo "<h3>Example Code Migration:</h3>\n";
echo "<pre><code>\n";
echo "// OLD WAY (deprecated)\n";
echo "// \$receipt = verifyReceipt(\$receiptData, \$password);\n\n";
echo "// NEW WAY\n";
echo "\$client = new AppleClient();\n";
echo "\$client->setApiKey(\$apiKeyPath);\n";
echo "\$client->setKeyIdentifier(\$keyIdentifier);\n";
echo "\$client->setIssuerId(\$issuerId);\n\n";
echo "\$appStoreServer = new AppleService_AppStoreServer(\$client);\n";
echo "\$validator = new InAppPurchaseValidator(\$appStoreServer, \$bundleId);\n\n";
echo "// Validate individual transaction\n";
echo "\$transaction = \$validator->validateTransaction(\$transactionId);\n\n";
echo "// Get complete history\n";
echo "\$history = \$validator->getTransactionHistory(\$originalTransactionId);\n";
echo "</code></pre>\n";
echo "</div>\n";

?>