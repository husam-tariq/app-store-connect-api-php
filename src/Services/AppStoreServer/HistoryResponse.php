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

class HistoryResponse extends \Cantie\AppStoreConnect\Model
{
    /**
     * @var string
     */
    protected $revision;

    /**
     * @var bool
     */
    protected $hasMore;

    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @var string
     */
    protected $appAppleId;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var array
     */
    protected $signedTransactions;

    /**
     * @return string
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @param string $revision
     * @return $this
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHasMore()
    {
        return $this->hasMore;
    }

    /**
     * @param bool $hasMore
     * @return $this
     */
    public function setHasMore($hasMore)
    {
        $this->hasMore = $hasMore;
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
     * @return array
     */
    public function getSignedTransactions()
    {
        return $this->signedTransactions;
    }

    /**
     * @param array $signedTransactions
     * @return $this
     */
    public function setSignedTransactions($signedTransactions)
    {
        $this->signedTransactions = $signedTransactions;
        return $this;
    }

    /**
     * Decode all signed transactions
     * @return array Array of decoded transaction information
     */
    public function getDecodedTransactions()
    {
        if (!$this->signedTransactions) {
            return [];
        }

        $decodedTransactions = [];
        foreach ($this->signedTransactions as $signedTransaction) {
            $parts = explode('.', $signedTransaction);
            if (count($parts) === 3) {
                $payload = $parts[1];
                $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
                $decodedTransactions[] = json_decode($decodedPayload, true);
            }
        }

        return $decodedTransactions;
    }
}