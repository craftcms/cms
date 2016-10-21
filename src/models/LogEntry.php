<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\validators\DateTimeValidator;

/**
 * Class LogEntry model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LogEntry extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var \DateTime Date time
     */
    public $dateTime;

    /**
     * @var string Level
     */
    public $level;

    /**
     * @var integer Category
     */
    public $category;

    /**
     * @var array Get
     */
    public $get;

    /**
     * @var array Post
     */
    public $post;

    /**
     * @var array Cookie
     */
    public $cookie;

    /**
     * @var array Session
     */
    public $session;

    /**
     * @var array Server
     */
    public $server;

    /**
     * @var array Profile
     */
    public $profile;

    /**
     * @var string Message
     */
    public $message;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['dateTime'], DateTimeValidator::class],
            [
                ['category'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                [
                    'dateTime',
                    'level',
                    'category',
                    'get',
                    'post',
                    'cookie',
                    'session',
                    'server',
                    'profile',
                    'message'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}
