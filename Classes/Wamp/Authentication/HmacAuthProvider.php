<?php
namespace FormatD\WampRouter\Wamp\Authentication;

use Neos\Flow\Annotations as Flow;

/**
 * A very basic authentication provider for authenticating flow accounts against a hmac
 * @see package FormatD.HmacAuthentication for details
 *
 * @Flow\Scope("prototype")
 */
class HmacAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient {

	/**
	 * @Flow\Inject
	 * @var \FormatD\HmacAuthentication\Service\HmacService
	 */
	protected $hmacService;

    /**
     * @return string
     */
    public function getMethodName()
	{
        return 'hmac';
    }

    /**
     * Process Authenticate message
     *
     * @param mixed $signature
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
	{
    	try {
			$authCredentials = $this->hmacService->decodeAndValidateAuthToken($signature);
			return ['SUCCESS', ['authid' => $authCredentials->username, 'authrole' => 'standard']];
		} catch (\Exception $e) {
			return ['FAILURE'];
		}
    }

}