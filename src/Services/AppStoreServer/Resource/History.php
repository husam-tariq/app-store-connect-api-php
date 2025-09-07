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
 * The "history" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appstoreserverService = new AppleService_AppStoreServer(...);
 *   $history = $appstoreserverService->history;
 *  </code>
 */
class History extends \Cantie\AppStoreConnect\Services\Resource
{
    /**
     * Get a customer's in-app purchase transaction history for your app.
     * (history.getTransactionHistory)
     *
     * @param string $originalTransactionId The original transaction identifier of the customer's transaction
     * @param array $optParams Optional parameters.
     * @return HistoryResponse
     * @throws \Cantie\AppStoreConnect\Exception
     */
    public function getTransactionHistory($originalTransactionId, $optParams = [])
    {
        $params = ['originalTransactionId' => $originalTransactionId];
        $params = array_merge($params, $optParams);
        return $this->call('getTransactionHistory', [$params], 'Cantie\AppStoreConnect\Services\AppStoreServer\HistoryResponse');
    }
}