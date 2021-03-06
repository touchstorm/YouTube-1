<?php

namespace SocialiteProviders\YouTube;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'YOUTUBE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['https://www.googleapis.com/auth/youtube.readonly'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://accounts.google.com/o/oauth2/auth',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );
        
        $contents = json_decode($response->getBody()->getContents(), true);
        
        if(!isset($contents['items'])) {
           throw new \Exception('The channel you selected is not a valid channel..', 404);   
        }
        
        if(empty($contents['items'])) {
           throw new \Exception('The channel you selected is not found on YouTube.', 404);   
        }

        return $contents['items'][0];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'     => $user['id'], 'nickname' => $user['snippet']['title'],
            'name'   => null, 'email' => null,
            'avatar' => $user['snippet']['thumbnails']['high']['url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
