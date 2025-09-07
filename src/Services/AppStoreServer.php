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

namespace Cantie\AppStoreConnect\Services;

/**
 * App Store Server API Service
 * 
 * This service provides access to Apple's App Store Server API for transaction validation
 * and server notifications, replacing the deprecated verifyReceipt endpoint.
 */
class AppStoreServer extends \Cantie\AppStoreConnect\Service
{
    public $transactions;
    public $history;
    public $subscriptionStatuses;
    public $notifications;

    public function __construct($clientOrConfig = [], $rootUrl = null)
    {
        parent::__construct($clientOrConfig);
        $this->rootUrl = $rootUrl ?: 'https://api.storekit.itunes.apple.com/';
        $this->servicePath = '';
        $this->batchPath = 'batch';
        $this->version = 'inApps/v1';
        $this->serviceName = 'appstoreserver';

        $this->transactions = new AppStoreServer\Resource\Transactions(
            $this,
            $this->serviceName,
            'transactions',
            [
                'methods' => [
                    'getTransactionInfo' => [
                        'path' => '/inApps/v1/transactions/{transactionId}',
                        'httpMethod' => 'GET',
                        'parameters' => [
                            'transactionId' => [
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->history = new AppStoreServer\Resource\History(
            $this,
            $this->serviceName,
            'history',
            [
                'methods' => [
                    'getTransactionHistory' => [
                        'path' => '/inApps/v1/history/{originalTransactionId}',
                        'httpMethod' => 'GET',
                        'parameters' => [
                            'originalTransactionId' => [
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                            'revision' => [
                                'location' => 'query',
                                'type' => 'string',
                            ],
                            'startDate' => [
                                'location' => 'query',
                                'type' => 'integer',
                            ],
                            'endDate' => [
                                'location' => 'query',
                                'type' => 'integer',
                            ],
                            'productIds' => [
                                'location' => 'query',
                                'type' => 'array',
                            ],
                            'productTypes' => [
                                'location' => 'query',
                                'type' => 'array',
                            ],
                            'sort' => [
                                'location' => 'query',
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->subscriptionStatuses = new AppStoreServer\Resource\SubscriptionStatuses(
            $this,
            $this->serviceName,
            'subscriptionStatuses',
            [
                'methods' => [
                    'getAllSubscriptionStatuses' => [
                        'path' => '/inApps/v1/subscriptions/{originalTransactionId}',
                        'httpMethod' => 'GET',
                        'parameters' => [
                            'originalTransactionId' => [
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                            'status' => [
                                'location' => 'query',
                                'type' => 'array',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->notifications = new AppStoreServer\Resource\Notifications(
            $this,
            $this->serviceName,
            'notifications',
            [
                'methods' => [
                    'requestTestNotification' => [
                        'path' => '/inApps/v1/notifications/test',
                        'httpMethod' => 'POST',
                        'parameters' => [],
                    ],
                    'getTestNotificationStatus' => [
                        'path' => '/inApps/v1/notifications/test/{testNotificationToken}',
                        'httpMethod' => 'GET',
                        'parameters' => [
                            'testNotificationToken' => [
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}