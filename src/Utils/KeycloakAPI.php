<?php
    /**
     * Created by PhpStorm.
     * User: miroslav
     * Date: 23/04/2019
     * Time: 09:00
     */

    namespace Ataccama\Utils;


    use Ataccama\Adapters\Keycloak;
    use Ataccama\Adapters\KeycloakExtended;
    use Ataccama\Exceptions\CurlException;
    use Ataccama\Exceptions\UnknownError;
    use Nette\Utils\Validators;


    class KeycloakAPI
    {
        /**
         * @param Keycloak $keycloak
         * @param string   $authorizationCode
         * @return AuthorizationResponse
         * @throws CurlException
         * @throws UnknownError
         */
        public static function getAuthorization(Keycloak $keycloak, string $authorizationCode): AuthorizationResponse
        {
            $response = Curl::post("$keycloak->host/auth/realms/$keycloak->realmId/protocol/openid-connect/token", [
                "Content-Type" => "application/x-www-form-urlencoded"
            ], [
                'grant_type'   => 'authorization_code',
                'code'         => $authorizationCode,
                'client_id'    => $keycloak->clientId,
                'redirect_uri' => $keycloak->redirectUri
            ]);

            if (isset($response->body->error)) {
                throw new CurlException($response->body->error . ": " . $response->body->error_description);
            }

            if (isset($response->body->access_token)) {
                return new AuthorizationResponse($response->body);
            }

            throw new UnknownError("???");
        }

        /**
         * @param KeycloakExtended $keycloak
         * @return AuthorizationResponse
         * @throws CurlException
         * @throws UnknownError
         */
        public static function getApiAuthorization(KeycloakExtended $keycloak): AuthorizationResponse
        {
            $response = Curl::post("$keycloak->host/auth/realms/$keycloak->realmId/protocol/openid-connect/token", [
                "Content-Type" => "application/x-www-form-urlencoded"
            ], [
                'grant_type'    => 'password',
                'client_id'     => $keycloak->apiClientId,
                'client_secret' => $keycloak->apiClientSecret,
                'username'      => $keycloak->apiUsername,
                'password'      => $keycloak->apiPassword
            ]);

            if (isset($response->body->error)) {
                throw new CurlException($response->body->error . ": " . $response->body->error_description);
            }

            if (isset($response->body->access_token)) {
                return new AuthorizationResponse($response->body);
            }

            throw new UnknownError("???");
        }

        /**
         * @param KeycloakExtended $keycloak
         * @param string           $username
         * @param string           $firstname
         * @param string           $lastname
         * @param string           $email
         * @param bool             $enabled
         * @param array            $groups
         * @return bool
         * @throws CurlException
         */
        public static function createUser(
            KeycloakExtended $keycloak,
            string $username,
            string $firstname,
            string $lastname,
            string $email,
            bool $enabled = true,
            array $groups = ['default-group']
        ): bool {
            $response = Curl::post("$keycloak->host/auth/admin/realms/$keycloak->realmId/users", [
                "Content-Type"  => "application/json",
                "Authorization" => "Bearer " . $keycloak->apiAccessToken->bearer
            ], json_encode([
                'username'  => $username,
                'firstName' => $firstname,
                'lastName'  => $lastname,
                "email"     => $email,
                "enabled"   => $enabled,
                "groups"    => $groups
            ]));

            if ($response->code == 201) {
                return true;
            }

            throw new CurlException("User creation failed. HTTP response code: $response->code");
        }

        /**
         * @param Keycloak     $keycloak
         * @param RefreshToken $userRefreshToken
         * @return AuthorizationResponse
         * @throws CurlException
         * @throws UnknownError
         */
        public static function reauthorize(Keycloak $keycloak, RefreshToken $userRefreshToken): AuthorizationResponse
        {
            $response = Curl::post("$keycloak->host/auth/realms/$keycloak->realmId/protocol/openid-connect/token", [
                "Content-Type" => "application/x-www-form-urlencoded"
            ], [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $userRefreshToken->refreshToken,
                'client_id'     => $keycloak->clientId,
                'redirect_uri'  => $keycloak->redirectUri
            ]);

            if (isset($response->body->error)) {
                throw new CurlException($response->body->error . ": " . $response->body->error_description);
            }

            if (isset($response->body->access_token)) {
                return new AuthorizationResponse($response->body);
            }

            throw new UnknownError("???");
        }

        /**
         * @param Keycloak     $keycloak
         * @param RefreshToken $userRefreshToken
         * @return bool
         * @throws CurlException
         */
        public static function logout(Keycloak $keycloak, RefreshToken $userRefreshToken): bool
        {
            $response = Curl::post("$keycloak->host/auth/realms/$keycloak->realmId/protocol/openid-connect/logout", [
                "Content-Type" => "application/x-www-form-urlencoded"
            ], [
                "refresh_token" => $userRefreshToken->refreshToken,
                "client_id"     => $keycloak->clientId
            ]);

            if ($response->code == 200) {
                return true;
            }

            if (isset($response->body->error)) {
                throw new CurlException("HTTP $response->code: " . $response->body->error . ": " .
                    $response->body->error_description);
            } else {
                throw new CurlException("HTTP $response->code: " . $response->error);
            }
        }

        public static function userExists(KeycloakExtended $keycloak, string $email): bool
        {
            $response = Curl::get("$keycloak->host/auth/admin/realms/$keycloak->realmId/users?email=$email", [
                "Authorization" => "Bearer " . $keycloak->apiAccessToken->bearer
            ]);

            if (isset($response->body[0]->username)) {
                return ($email == $response->body[0]->username);
            }

            return false;
        }
    }