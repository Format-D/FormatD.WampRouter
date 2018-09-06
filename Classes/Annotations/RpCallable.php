<?php
namespace FormatD\WampRouter\Annotations;

/**
 * Annotation for marking wamp callable methods
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class RpCallable {

	/**
	 * Uri endpoint for wamp router
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * realms in wich the endpoint is callable
	 *
	 * @var array
	 */
	protected $realms = NULL;

	/**
	 * @param array $values
	 */
	public function __construct(array $values)
	{
		if (!isset($values['value']) && !isset($values['uri'])) {
			throw new \InvalidArgumentException('A WampCallable annotation must specify a uri.', 1536231549);
		}
		$this->uri = isset($values['uri']) ? $values['uri'] : $values['value'];

		if (isset($values['realms'])) {
			$this->realms = $values['realms'];
		}
	}

	/**
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * @return array
	 */
	public function getRealms()
	{
		return $this->realms;
	}

}