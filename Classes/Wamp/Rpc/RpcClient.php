<?php
namespace FormatD\WampRouter\Wamp\Rpc;

use Neos\Flow\Annotations as Flow;
use \FormatD\WampRouter\Annotations\RpCallable;

/**
 * Internal client providing rpc endpoints for annotated methods in flow
 *
 * @Flow\Scope("prototype")
 */
class RpcClient extends \Thruway\Peer\Client {

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $rpcMethods = [];

	/**
	 * Detect remote callable methods by annotation
	 */
	public function initializeObject()
	{
		$this->rpcMethods = static::getRpcMethods($this->objectManager);
	}

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
	{
        \Thruway\Logging\Logger::info($this, 'Generic RPC Client registered successfully');

		$rpcMethods = $this->rpcMethods;

		foreach ($rpcMethods as $className => $methods) {
			$class = $this->objectManager->get($className);

			foreach ($methods as $methodName) {
				/** @var \FormatD\WampRouter\Annotations\RpCallable $annotation */
				$annotation = $this->reflectionService->getMethodAnnotation($className, $methodName, RpCallable::class);

				if ($annotation->getRealms() === NULL || array_search($this->getRealm(), $annotation->getRealms()) !== FALSE) {
					\Thruway\Logging\Logger::info($this, 'Registering new RPC Endpoint: ' . $annotation->getUri());
					$session->register($annotation->getUri(), [$class, $methodName]);
				}
			}
		}
    }

	/**
	 * Returns all class names and methods that are rpc callable
	 *
	 * @Flow\CompileStatic
	 * @param \Neos\Flow\ObjectManagement\ObjectManagerInterface $objectManager
	 * @return array Array of classes with methods containing a RpCallable method
	 */
	protected static function getRpcMethods(\Neos\Flow\ObjectManagement\ObjectManagerInterface $objectManager)
	{
		$reflectionService = $objectManager->get(\Neos\Flow\Reflection\ReflectionService::class);
		$classNames = $reflectionService->getClassesContainingMethodsAnnotatedWith(RpCallable::class);

		$methods = [];
		foreach($classNames as $className) {
			$methods[$className] = $reflectionService->getMethodsAnnotatedWith($className, RpCallable::class);
		}

		return $methods;
	}

}