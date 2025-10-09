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

namespace Cantie\AppStoreConnect\Services\AppStoreServer\Resource;

/**
 * The "transactions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appstoreserverService = new AppleService_AppStoreServer(...);
 *   $transactions = $appstoreserverService->transactions;
 *  </code>
 */
class Transactions extends \Cantie\AppStoreConnect\Services\Resource
{
    /**
     * Get information about a single transaction for your app.
     * (transactions.getTransactionInfo)
     *
     * @param string $transactionId The identifier of a transaction that belongs to the customer
     * @param array $optParams Optional parameters.
     * @return TransactionInfoResponse
     * @throws \Cantie\AppStoreConnect\Exception
     */
    public function getTransactionInfo($transactionId, $optParams = [])
    {
        $params = ['transactionId' => $transactionId];
        $params = array_merge($params, $optParams);
        return $this->call('getTransactionInfo', [$params], 'Cantie\AppStoreConnect\Services\AppStoreServer\TransactionInfoResponse');
    }
}