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

use Cantie\AppStoreConnect\Services\AppStoreServer;

/**
 * InAppPurchase Validator using the new App Store Server API
 * 
 * This class replaces the deprecated verifyReceipt endpoint with the new
 * App Store Server API for validating transactions and managing subscriptions.
 */
class InAppPurchaseValidator
{
    /**
     * @var AppStoreServer
     */
    private $appStoreServerService;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * @param AppStoreServer $appStoreServerService
     * @param string $bundleId
     */
    public function __construct(AppStoreServer $appStoreServerService, $bundleId)
    {
        $this->appStoreServerService = $appStoreServerService;
        $this->bundleId = $bundleId;
    }

    /**
     * Validate a single transaction using the new App Store Server API
     * 
     * @param string $transactionId The transaction ID to validate
     * @return array|null Validated transaction information or null if invalid
     * @throws \Cantie\AppStoreConnect\Exception
     */
    public function validateTransaction($transactionId)
    {
        try {
            $response = $this->appStoreServerService->transactions->getTransactionInfo($transactionId);
            $decodedTransaction = $response->getDecodedTransactionInfo();
            
            // Verify the bundle ID matches
            if ($decodedTransaction && isset($decodedTransaction['bundleId'])) {
                if ($decodedTransaction['bundleId'] === $this->bundleId) {
                    return $decodedTransaction;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            throw new \Cantie\AppStoreConnect\Exception('Transaction validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction history for a customer
     * 
     * @param string $originalTransactionId The original transaction ID
     * @param array $options Optional parameters (startDate, endDate, productIds, etc.)
     * @return array Array of validated transactions
     * @throws \Cantie\AppStoreConnect\Exception
     */
    public function getTransactionHistory($originalTransactionId, $options = [])
    {
        try {
            $response = $this->appStoreServerService->history->getTransactionHistory($originalTransactionId, $options);
            $decodedTransactions = $response->getDecodedTransactions();
            
            // Filter transactions by bundle ID
            $validTransactions = [];
            foreach ($decodedTransactions as $transaction) {
                if (isset($transaction['bundleId']) && $transaction['bundleId'] === $this->bundleId) {
                    $validTransactions[] = $transaction;
                }
            }
            
            return [
                'transactions' => $validTransactions,
                'hasMore' => $response->getHasMore(),
                'revision' => $response->getRevision(),
                'environment' => $response->getEnvironment()
            ];
        } catch (\Exception $e) {
            throw new \Cantie\AppStoreConnect\Exception('Transaction history retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Get subscription statuses for a customer
     * 
     * @param string $originalTransactionId The original transaction ID
     * @param array $status Optional array of status values to filter by
     * @return array Array of subscription statuses
     * @throws \Cantie\AppStoreConnect\Exception
     */
    public function getSubscriptionStatuses($originalTransactionId, $status = [])
    {
        try {
            $options = [];
            if (!empty($status)) {
                $options['status'] = $status;
            }
            
            $response = $this->appStoreServerService->subscriptionStatuses->getAllSubscriptionStatuses($originalTransactionId, $options);
            $decodedStatuses = $response->getDecodedSubscriptionStatuses();
            
            // Verify bundle ID matches
            if ($response->getBundleId() === $this->bundleId) {
                return [
                    'subscriptionStatuses' => $decodedStatuses,
                    'environment' => $response->getEnvironment(),
                    'bundleId' => $response->getBundleId(),
                    'appAppleId' => $response->getAppAppleId()
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            throw new \Cantie\AppStoreConnect\Exception('Subscription status retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate a receipt data (legacy support - migrates to new API)
     * 
     * This method helps migrate from the old verifyReceipt approach by extracting
     * transaction IDs from receipt data and validating them with the new API.
     * 
     * @param string $receiptData Base64 encoded receipt data
     * @return array Validation result
     * @deprecated Use validateTransaction() or getTransactionHistory() instead
     */
    public function validateReceiptData($receiptData)
    {
        // For legacy support, you would need to extract transaction IDs from the receipt
        // and then use the new validation methods. This is a placeholder for migration.
        throw new \BadMethodCallException(
            'Receipt validation using receiptData is deprecated. ' .
            'Use validateTransaction() with transaction IDs or getTransactionHistory() instead. ' .
            'See Apple\'s migration guide for extracting transaction IDs from receipts.'
        );
    }

    /**
     * Check if a transaction is valid and not refunded
     * 
     * @param array $transaction Decoded transaction data
     * @return bool True if transaction is valid
     */
    public function isTransactionValid($transaction)
    {
        if (!$transaction) {
            return false;
        }

        // Check if transaction is present and has required fields
        if (!isset($transaction['transactionId']) || !isset($transaction['productId'])) {
            return false;
        }

        // Check if transaction is not refunded
        if (isset($transaction['revocationDate']) && $transaction['revocationDate'] > 0) {
            return false;
        }

        // Check bundle ID
        if (isset($transaction['bundleId']) && $transaction['bundleId'] !== $this->bundleId) {
            return false;
        }

        return true;
    }

    /**
     * Check if a subscription is active
     * 
     * @param array $subscriptionStatus Decoded subscription status data
     * @return bool True if subscription is active
     */
    public function isSubscriptionActive($subscriptionStatus)
    {
        if (!$subscriptionStatus || !isset($subscriptionStatus['lastTransactions'])) {
            return false;
        }

        foreach ($subscriptionStatus['lastTransactions'] as $transaction) {
            if (isset($transaction['status']) && $transaction['status'] == 1) { // Active status
                if (isset($transaction['transactionInfo']['expiresDate'])) {
                    $expiresDate = $transaction['transactionInfo']['expiresDate'];
                    // Check if subscription hasn't expired
                    return $expiresDate > time() * 1000; // Apple uses milliseconds
                }
            }
        }

        return false;
    }
}