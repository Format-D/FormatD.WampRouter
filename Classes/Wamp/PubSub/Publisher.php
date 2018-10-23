<?php
namespace FormatD\WampRouter\Wamp\PubSub;

use Neos\Flow\Annotations as Flow;
use FormatD\WampRouter\Annotations as WAMP;

/**
 * Service for publishing data to a topic
 *
 * @Flow\Scope("singleton")
 */
class Publisher {

	/**
	 * @param string $realm
	 * @param string $topic
	 * @param array $args
	 * @param \Thruway\Authentication\ClientAuthenticationInterface $authenticator
	 * @param string $address (Protocol, IP and Port of the WampRouter)
	 * @return string
	 */
	public function publish($realm, $topic, $args, $authenticator = NULL, $address = 'ws://127.0.0.1:8080') {

		// workaound to prevent thruway to output to the browser
		\Thruway\Logging\Logger::set(new \Psr\Log\NullLogger());

		$result     = null;
		$connection = new \Thruway\Connection([
			'realm' => $realm,
			'url'   => $address,
		]);

		$connection->getClient()->setAttemptRetry(false);

		if ($authenticator) {
			$connection->getClient()->addClientAuthenticator($authenticator);
		}

		$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $args, $topic, &$result) {
			//publish an event
			$session->publish($topic, [$args], [], ["acknowledge" => true])->then(
				function () use ($connection, &$result) {
					$result = "published";
					$connection->close(); // close connection after publish
				},
				function ($error) use ($connection, &$result) {
					// publish failed
					$result = $error;
					$connection->close();
				}
			);
		});

		$connection->on('error', function ($errorCode, $message) use ($topic) {
			throw new \FormatD\WampRouter\Exception('Error during publish to topic "' . $topic . '": Error ' . $errorCode);
		});

		$connection->open();

		if ($result !== 'published') {
			throw new \FormatD\WampRouter\Exception('Error during publish to topic "' . $topic . '": Result ' . $result);
		}

		return $result;
	}

}
?>