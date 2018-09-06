<?php
namespace FormatD\WampRouter\Command;

use Neos\Flow\Annotations as Flow;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

/**
 * Command controller for starting the WAMP router
 *
 * @Flow\Scope("singleton")
 */
class WampRouterCommandController extends \Neos\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Runs the WAMP Router. This should be run as a deamon in production.
	 *
	 * @param string $instance Instancename (configured in the Settings.yaml)
	 * @return void
	 */
	public function runCommand($instance = NULL)
	{
		$now = new \DateTime('now');

	    if (!$instance) {
			$instance = $this->configurationManager->getConfiguration('Settings', 'FormatD.WampRouter.defaultInstance');
		}

		$this->outputLine('Starting up WAMP Router instance "' . $instance . '"... ');
		$this->outputLine('Now is ' . $now->format('d.m.Y H:i'));

	    $configuration = $this->getInstanceConfiguration($instance);

        $router = new Router();

		// authentication manager
		$this->outputLine('Configuring default thruway authentication manager');
		$authenticationManager = new \Thruway\Authentication\AuthenticationManager();
		$router->registerModule($authenticationManager);

        // setting up realms
        foreach ($configuration['realms'] as $realmConfiguration) {
			$this->outputLine('Configuring realm "' . $realmConfiguration['name'] . '"');

			// authorisation (roles)
			$authorizationManager = $this->getConfiguredAuthorizationManager($realmConfiguration);
			$router->registerModule($authorizationManager);

			// internal clients
			foreach ($realmConfiguration['internalClients'] as $clientConfiguration) {
				$this->outputLine('Configuring internal client "' . $clientConfiguration['implementationClass'] . '"');
				$clientClassName = $clientConfiguration['implementationClass'];
				$client = new $clientClassName($realmConfiguration['name']);
				$router->addInternalClient($client);
			}

			// generic RPC client for annotated methods
			$router->addInternalClient(new \FormatD\WampRouter\Wamp\Rpc\RpcClient($realmConfiguration['name']));
		}

		// authentication providers
		foreach ($configuration['authentication']['providers'] as $authProviderConfiguration) {
			$this->outputLine('Configuring authentication provider "' . $authProviderConfiguration['implementationClass'] . '"');
			$authProviderClassName = $authProviderConfiguration['implementationClass'];
			$authProvider = new $authProviderClassName($authProviderConfiguration['realms']);
			$router->addInternalClient($authProvider);
		}

		// transports
		foreach ($configuration['transports'] as $transportConfiguration) {
			$this->outputLine('Configuring Transport "' . $transportConfiguration['implementationClass'] . '"');
			$transportClassName = $transportConfiguration['implementationClass'];
			$transport = new $transportClassName(...$transportConfiguration['arguments']);
			$router->registerModule($transport);
		}

		$router->start();
	}


	/**
	 * Fetches the configuration for the named instance
	 *
	 * @param string $instanceIdentifier
	 * @return array
	 */
	public function getInstanceConfiguration($instanceIdentifier = 'default')
	{
		$configuration = $this->configurationManager->getConfiguration('Settings', 'FormatD.WampRouter.instances.' . $instanceIdentifier);
		return $configuration;
	}

	/**
	 * Creates a ConfigurationManager with the roles configured in the instance configuration
	 *
	 * @param array $realmConfiguration
	 * @return \Thruway\Authentication\AuthorizationManager
	 */
	protected function getConfiguredAuthorizationManager($realmConfiguration)
	{
		$authorizationManager = new \Thruway\Authentication\AuthorizationManager($realmConfiguration['name']);

		foreach ($realmConfiguration['authorizationRules'] as $ruleConfiguration) {
			$rule = new \stdClass();
			$rule->role   = $ruleConfiguration['role'];
			$rule->action = $ruleConfiguration['action'];
			$rule->uri    = $ruleConfiguration['uri'];
			$rule->allow  = $ruleConfiguration['allow'];
			$authorizationManager->addAuthorizationRule([$rule]);
		}

		return $authorizationManager;
	}

	/**
	 * Outputs specified text to the console window and appends a line break
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 * @see output()
	 * @see outputLines()
	 * @api
	 */
	protected function outputLine($text = '', array $arguments = [])
	{
		$text = '[FLOW] ' . $text;
		parent::outputLine($text, $arguments);
	}

}
