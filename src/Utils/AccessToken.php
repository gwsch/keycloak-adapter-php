<?php
    /**
     * Created by PhpStorm.
     * User: miroslav
     * Date: 23/04/2019
     * Time: 09:01
     */

    namespace Ataccama\Utils;

    use Nette\SmartObject;


    /**
     * Class AccessToken
     * @package Ataccama\Utils
     * @property-read string $bearer;
     */
    class AccessToken extends Token
    {
        use SmartObject;

        /** @var string */
        protected $bearer;

        /**
         * AccessToken constructor.
         * @param string $accessToken
         * @param int    $expires_in
         */
        public function __construct(string $accessToken, int $expires_in)
        {
            parent::__construct(time() + $expires_in);
            $this->bearer = $accessToken;
        }

        /**
         * @return UserIdentity
         */
        public function getUserIdentity(): UserIdentity
        {
            $exploded = explode('.', $this->bearer);
            $stdObject = json_decode(base64_decode($exploded[1]));

            return new UserIdentity($stdObject);
        }

        /**
         * @return string
         */
        public function getBearer(): string
        {
            return $this->bearer;
        }
    }