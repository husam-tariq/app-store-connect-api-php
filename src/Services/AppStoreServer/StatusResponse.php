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

namespace Cantie\AppStoreConnect\Services\AppStoreServer;

class StatusResponse extends \Cantie\AppStoreConnect\Model
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @var string
     */
    protected $appAppleId;

    /**
     * @var array
     */
    protected $data;

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return string
     */
    public function getBundleId()
    {
        return $this->bundleId;
    }

    /**
     * @param string $bundleId
     * @return $this
     */
    public function setBundleId($bundleId)
    {
        $this->bundleId = $bundleId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppAppleId()
    {
        return $this->appAppleId;
    }

    /**
     * @param string $appAppleId
     * @return $this
     */
    public function setAppAppleId($appAppleId)
    {
        $this->appAppleId = $appAppleId;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get subscription statuses with decoded renewal info
     * @return array Array of subscription statuses with decoded renewal information
     */
    public function getDecodedSubscriptionStatuses()
    {
        if (!$this->data) {
            return [];
        }

        $decodedStatuses = [];
        foreach ($this->data as $subscriptionGroup) {
            $groupData = [
                'subscriptionGroupIdentifier' => $subscriptionGroup['subscriptionGroupIdentifier'] ?? null,
                'lastTransactions' => []
            ];

            if (isset($subscriptionGroup['lastTransactions'])) {
                foreach ($subscriptionGroup['lastTransactions'] as $transaction) {
                    $decodedTransaction = [];
                    
                    // Decode signed transaction info
                    if (isset($transaction['signedTransactionInfo'])) {
                        $parts = explode('.', $transaction['signedTransactionInfo']);
                        if (count($parts) === 3) {
                            $payload = $parts[1];
                            $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                            $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
                            $decodedTransaction['transactionInfo'] = json_decode($decodedPayload, true);
                        }
                    }

                    // Decode signed renewal info
                    if (isset($transaction['signedRenewalInfo'])) {
                        $parts = explode('.', $transaction['signedRenewalInfo']);
                        if (count($parts) === 3) {
                            $payload = $parts[1];
                            $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                            $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
                            $decodedTransaction['renewalInfo'] = json_decode($decodedPayload, true);
                        }
                    }

                    $decodedTransaction['status'] = $transaction['status'] ?? null;
                    $groupData['lastTransactions'][] = $decodedTransaction;
                }
            }

            $decodedStatuses[] = $groupData;
        }

        return $decodedStatuses;
    }
}