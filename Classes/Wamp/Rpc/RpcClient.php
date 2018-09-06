<?php
namespace FormatD\WampRouter\Wamp\Rpc;

use Neos\Flow\Annotations as Flow;

/**
 * Internal client providing rpc endpoints for annotated methods in flow
 *
 * @Flow\Scope("prototype")
 */
class RpcClient extends \Thruway\Peer\Client {

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
	 */
	protected $objectManager;

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
	{
        \Thruway\Logging\Logger::info($this, 'Generic RPC Client registered successfully');

		$rpcClasses = $this->reflectionService->getClassesContainingMethodsAnnotatedWith(\FormatD\WampRouter\Annotations\RpCallable::class);

		foreach ($rpcClasses as $className) {
			$class = $this->objectManager->get($className);
			$methods = $this->reflectionService->getMethodsAnnotatedWith($className, \FormatD\WampRouter\Annotations\RpCallable::class);

			foreach ($methods as $methodName) {
				/** @var \FormatD\WampRouter\Annotations\RpCallable $annotation */
				$annotation = $this->reflectionService->getMethodAnnotation($className, $methodName, \FormatD\WampRouter\Annotations\RpCallable::class);

				if ($annotation->getRealms() === NULL || array_search($this->getRealm(), $annotation->getRealms()) !== FALSE) {
					\Thruway\Logging\Logger::info($this, 'Registering new RPC Endpoint: ' . $annotation->getUri());
					$session->register($annotation->getUri(), [$class, $methodName]);
				}
			}
		}
    }

}