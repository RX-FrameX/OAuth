<?php

/**
 * @package     FrameX (FX) OAuth Plugin
 * @link        https://localzet.gitbook.io
 * 
 * @author      localzet <creator@localzet.ru>
 * 
 * @copyright   Copyright (c) 2018-2020 Zorin Projects 
 * @copyright   Copyright (c) 2020-2022 NONA Team
 * 
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

namespace plugin\oauth\app\provider;

use plugin\oauth\app\adapter\OAuth2;
use plugin\oauth\app\exception\UnexpectedApiResponseException;

use plugin\oauth\app\entity;
use support\Collection;

/**
 * WeChat International OAuth2 provider adapter.
 */
class WeChat extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'snsapi_login,snsapi_userinfo,scope.userInfo';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.wechat.com/sns/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.wechat.com/sns/oauth2/access_token';

    /**
     * Refresh Token Endpoint
     * @var string
     */
    protected $tokenRefreshUrl = 'https://api.wechat.com/sns/oauth2/refresh_token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://api.wechat.com/sns/auth';

    /**
     * {@inheritdoc}
     */
    protected $tokenExchangeMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $tokenRefreshMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = ''; // Not available

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'appid' => $this->clientId
        ];
        unset($this->AuthorizeUrlParameters['client_id']);

        $this->tokenExchangeParameters += [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret
        ];
        unset($this->tokenExchangeParameters['client_id']);
        unset($this->tokenExchangeParameters['client_secret']);

        $this->tokenRefreshParameters += [
            'appid' => $this->clientId
        ];

        $this->apiRequestParameters = [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('openid', $collection->get('openid'));
        $this->storeData('access_token', $collection->get('access_token'));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $openid = $this->getStoredData('openid');
        $access_token = $this->getStoredData('access_token');

        $response = $this->apiRequest('userinfo', 'GET', ['openid' => $openid, 'access_token' => $access_token]);

        $data = new Collection($response);

        if (!$data->exists('openid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new entity\Profile();

        $userProfile->identifier = $data->get('openid');
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL = $data->get('headimgurl');
        $userProfile->city = $data->get('city');
        $userProfile->region = $data->get('province');
        $userProfile->country = $data->get('country');
        $genders = ['', 'male', 'female'];
        $userProfile->gender = $genders[(int)$data->get('sex')];

        return $userProfile;
    }
}
