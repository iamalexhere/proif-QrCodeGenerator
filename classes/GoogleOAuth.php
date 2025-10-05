<?php

/**
 * Class GoogleOAuth
 * 
 * Handles Google OAuth 2.0 authentication flow
 * Requires Google Client Library: composer require google/apiclient
 */

require_once __DIR__ . '/../config/Config.php';

class GoogleOAuth {
    private $client;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setClientId(Config::get('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(Config::get('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(Config::get('GOOGLE_REDIRECT_URI'));
        $this->client->addScope('email');
        $this->client->addScope('profile');
    }
    
    /**
     * Get Google OAuth login URL
     */
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Exchange authorization code for access token and get user info
     */
    public function authenticate($code) {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error']);
            }
            
            $this->client->setAccessToken($token);
            
            // Get user info
            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            
            return [
                'id' => $userInfo->id,
                'sub' => $userInfo->id,
                'email' => $userInfo->email,
                'name' => $userInfo->name,
                'picture' => $userInfo->picture,
                'verified_email' => $userInfo->verifiedEmail
            ];
            
        } catch (Exception $e) {
            error_log('Google OAuth error: ' . $e->getMessage());
            return false;
        }
    }
}
