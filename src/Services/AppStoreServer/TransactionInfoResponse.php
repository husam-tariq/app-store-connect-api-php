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

class TransactionInfoResponse extends \Cantie\AppStoreConnect\Model
{
    /**
     * @var string
     */
    protected $signedTransactionInfo;

    /**
     * @return string
     */
    public function getSignedTransactionInfo()
    {
        return $this->signedTransactionInfo;
    }

    /**
     * @param string $signedTransactionInfo
     * @return $this
     */
    public function setSignedTransactionInfo($signedTransactionInfo)
    {
        $this->signedTransactionInfo = $signedTransactionInfo;
        return $this;
    }

    /**
     * Decode the signed transaction info JWT
     * @return array The decoded transaction information
     */
    public function getDecodedTransactionInfo()
    {
        if (!$this->signedTransactionInfo) {
            return null;
        }

        // Split the JWT into parts
        $parts = explode('.', $this->signedTransactionInfo);
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
}