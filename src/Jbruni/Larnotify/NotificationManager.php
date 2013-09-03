<?php namespace Jbruni\Larnotify;

use ArrayAccess;
use Countable;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\MessageProviderInterface;
use Illuminate\Support\MessageBag;

/**
 * Manage Notifications
 * 
 * @author J. Bruni
 */
class NotificationManager implements ArrayAccess, ArrayableInterface, Countable, JsonableInterface, MessageProviderInterface {

	/**
	 * MessageBag creation modes
	 */
	const DO_NOT_CREATE    = FALSE;
	const CREATE_ATTACHED  = TRUE;
	const CREATE_DETTACHED = NULL;

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Collection of MessageBags
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Create a new notification manager instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * @return NotificationManager   Allow fluent syntax using Facade
	 */
	public function get()
	{
		return $this;
	}

	/**
	 * @return array   Currently existent messageBag names
	 */
	public function getBags()
	{
		return array_unique(array_merge(array('default'), array_keys($this->messages)));
	}

	/**
	 * @param boolean $prefixBag   True returns "default.msg", "bag.type", etc; False (default) returns "msg", "type", etc
	 * @return array   Currently existent message types
	 */
	public function getTypes($prefixBag = FALSE)
	{
		$types = array();
		foreach ($this->messages as $bag => $messageBag)
		{
			foreach (array_keys($messageBag->getMessages()) as $bagType)
			{
				$types[] = ($prefixBag ? "$bag." : '') . $bagType;
			}
		}
		return array_unique(array_merge(array(($prefixBag ? 'default.' : '') . 'msg'), $types));
	}

	/**
	 * @param string $bag   Bag name
	 * @return array   Currently existent message types in the speficied bag
	 */
	public function getBagTypes($bag)
	{
		$bagTypes = array_keys($this->getMessageBag($bag, self::CREATE_DETTACHED)->getMessages());

		if ($bag == 'default')
		{
			$bagTypes = array_unique(array_merge(array('msg'), $bagTypes));
		}

		return $bagTypes;
	}

	/**
	 * This Notification Manager allows a kind of "namespacing" the messages and also selecting a "type" for each message
	 * It always use "default" namespace when none is provided, and "msg" type when none is provided
	 * To provide a different type, just use it directly: "error", "warning", "info" [the "default" namespace will be used]
	 * To provide a different namespace, append it to the message type: "requests.unread", "requests.read"
	 * To provide only the namespace, leave the type empty: "request." [the default "msg" type will be used]
	 * 
	 * @param string $where   The namespace and type specification (read above)
	 * @return array   Array with two strings: first is the "bagName" (namespace) and second is "messageType" (type)
	 */
	public function getBagType($where = 'default.msg')
	{
		if (strpos($where, '.') === FALSE)
		{
			$where = 'default.' . $where;
		}

		$result = explode('.', $where, 2);

		if (empty($result[1]))
		{
			$result[1] = 'msg';
		}

		return $result;
	}

	/**
	 * Add a notification
	 * 
	 * The first parameter is optional. If provided, must be a string. It can specify:
	 * 1) A "sprintf" template. Example: "sprintf:Your name is %s and you are %s"
	 * 2) A "view" name. Example: "view:messages.error"
	 * 3) A message type, with optional namespace. Examples: "warning", "requests.unread"
	 * First parameter defaults to "default.msg" ("default" namespace w/ default "msg" type)
	 * 
	 * Both "sprintf" and "view" are "reserved" message types.
	 * You can attach a namespace to the "sprintf" or "view" message types. Just prefix it. Example:
	 * Larnotify::add('widget.view:new_call', array('from' => 'Best Friend', 'when' => '10 minutes ago'));
	 * 
	 * The other parameter contains the message specific contents.
	 * It depends on what has been optionally set by the first parameter:
	 * 1) A STRING or an ARRAY containing the remaining "sprintf" arguments. Example 1: "J. Bruni" Example 2: array("J. Bruni", "male")
	 * 2) An ARRAY containing the template data. Example: array("name" => "J. Bruni", "country" => "Brazil")
	 * 3) You can specify a STRING containing a single message or an ARRAY of string messages.
	 * 
	 * @param string $template   OPTIONAL (see above)
	 * @param string|array $message   Message contents (see above)
	 */
	public function add($template = '', $message = '')
	{
		$multiple = FALSE;
		$where = $template;

		if (func_num_args() < 2)
		{
			$message = $template;
			$where = 'default.msg';
			$multiple = is_array($message);
		}

		if (preg_match('/^(([^.]+\.)?view):(.*)$/', $template, $matches) === 1)
		{
			$where = $matches[1];
			$message = array(
				'view' => $matches[3],
				'data' => $message,
			);
		}

		if (preg_match('/^(([^.]+\.)?sprintf):(.*)$/', $template, $matches) === 1)
		{
			$where = $matches[1];
			$message = array_merge(
				array($matches[3]),
				(array) $message
			);
		}

		list($bag, $type) = $this->getBagType($where);

		$messageBag = $this->getMessageBag($bag, self::CREATE_ATTACHED);

		if (!$multiple)
		{
			return $messageBag->add($type, $message);
		}

		foreach ($message as $msg)
		{
			$result = $messageBag->add($type, $msg);
		}

		return $result;
	}

	/**
	 * Retrieve notifications
	 * 
	 * @param string $where   Optional "namespace.type" (see "getBagType" documentation above)
	 * @return array   Notification messages
	 */
	public function getMessages($where = 'default.ALL')
	{
		list($bag, $type) = $this->getBagType($where);

		$messagesArray = $this->getMessageBag($bag, self::CREATE_DETTACHED)->getMessages();

		if ($type == 'ALL') { return $messagesArray; }

		return (isset($messagesArray[$type]) ? $messagesArray[$type] : array());
	}

	/**
	 * Retrieve notifications in JSON format
	 * 
	 * @param string $where   Optional "namespace.type" (see "getBagType" documentation above)
	 * @param integer $options   Options for the "json_encode" function
	 * @return string   Notification messages (JSON encoded)
	 */
	public function getJson($where = 'default.ALL', $options = 0)
	{
		return json_encode($this->getMessages($where), $options);
	}

	/**
	 * @param string $where   Optional "namespace.type" (see "getBagType" documentation above)
	 * @return string   Rendered view for the messages
	 */
	public function render($where = 'default.ALL')
	{
		$messages = $this->getMessages($where);

		list($bag, $type) = $this->getBagType($where);

		if ($type != 'ALL')
		{
			return $this->renderMessages($messages, $bag, $type);
		}

		$contents = array();
		foreach (array_keys($messages) as $type)
		{
			$contents[] = $this->renderMessages($messages[$type], $bag, $type);
		}
		return implode($this->getBlockSplitter(), $contents);
	}

	/**
	 * @param array $messages   Messages retrieved from bag
	 * @param string $bag   Bag name (namespace)
	 * @param string $type   Messages type
	 * @return string   Rendered messages
	 */
	public function renderMessages($messages, $bag, $type)
	{
		if (empty($messages)) { return ''; }

		$contents = array();

		switch($type)
		{
			case 'sprintf':
				foreach ($messages as $message)
				{
					$contents[] = call_user_func_array('sprintf', $message);
				}
				break;

			case 'view':
				foreach ($messages as $message)
				{
					$contents[] = $this->app->make('view')->make($message['view'], $message['data']);
				}
				break;

			default:
				$view = $this->getViewFor($bag, $type);

				if (!empty($view) && $this->app->make('view')->exists($view))
				{
					$template_variable = $this->app->make('config')->get('larnotify::msg_variable');
					return $this->app->make('view')->make($view, array($template_variable => $messages));
				}

				foreach ($messages as $message)
				{
					$contents[] = sprintf($this->getDefaultTemplate(), $message, $bag, $type);
				}
		}

		return implode($this->getBlockSplitter(), $contents);
	}

	/**
	 * @param string $bag   Bag name (namespace)
	 * @param string $type   Message type
	 * @return string   Configured view
	 */
	public function getViewFor($bag = 'default', $type = 'msg')
	{
		if ($this->app->make('view')->exists("$bag.$type")) return "$bag.$type";

		$views = $this->app->make('config')->get('larnotify::views');

		if (isset($views["$bag.$type"])) { return $views["$bag.$type"]; }

		if (isset($views["$type"])) { return $views["$type"]; }

		if (isset($views["default.$type"])) { return $views["default.$type"]; }

		if (isset($views['msg'])) { return $views['msg']; }

		if (isset($views['default.msg'])) { return $views['default.msg']; }

		return '';
	}

	/**
	 * @return string   Configured default template (argument for sprintf)
	 */
	public function getDefaultTemplate()
	{
		return $this->app->make('config')->get('larnotify::default_template', '%s');
	}

	/**
	 * @return string   Configured block splitter
	 */
	public function getBlockSplitter()
	{
		return $this->app->make('config')->get('larnotify::block_splitter', '');
	}

	/**
	 * @param string $where   Notification "namespace.type" (see "getBagType" documentation above)
	 * @param string $view   View template name
	 */
	public function setTemplate($where, $view)
	{
		$views = $this->app->make('config')->get('larnotify::views');
		$views[$where] = $view;
		$this->app->make('config')->set('larnotify::views', $views);
	}

	/**
	 * @param string $where   Notification "namespace.type" (see "getBagType" documentation above)
	 */
	public function unsetTemplate($where)
	{
		$views = $this->app->make('config')->get('larnotify::views');
		unset($views[$where]);
		$this->app->make('config')->set('larnotify::views', $views);
	}

	/**
	 * This helper allows easy inclusion of $messages in templates
	 * 
	 * @return string   Generated View 
	 */
	public function __toString()
	{
		return $this->render();
	}

	/*
	|--------------------------------------------------------------------------
	| ArrayableInterface implementation
	|--------------------------------------------------------------------------
	*/

	public function toArray()
	{
		return $this->getMessages();
	}

	/*
	|--------------------------------------------------------------------------
	| JsonableInterface implementation
	|--------------------------------------------------------------------------
	*/

	public function toJson($options = 0)
	{
		return $this->getJson('default.ALL', $options);
	}

	/*
	|--------------------------------------------------------------------------
	| MessageProviderInterface implementation
	|--------------------------------------------------------------------------
	*/

	public function getMessageBag($bag = 'default', $mode = self::DO_NOT_CREATE)
	{
		if (isset($this->messages[$bag]))
		{
			return $this->messages[$bag];
		}

		if ($bag == 'default')
		{
			$mode = self::CREATE_ATTACHED;
		}

		if ($mode === self::DO_NOT_CREATE)
		{
			return;
		}

		$messageBag = new MessageBag();

		if ($mode === self::CREATE_ATTACHED)
		{
			$this->messages[$bag] = $messageBag;
		}

		return $messageBag;
	}

	/*
	|--------------------------------------------------------------------------
	| Countable interface implementation
	|--------------------------------------------------------------------------
	*/

	public function count()
	{
		$count = 0;
		foreach ($this->messages as $messageBag)
		{
			$count += count($messageBag->all());
		}
		return $count;
	}

	/*
	|--------------------------------------------------------------------------
	| ArrayAccess interface implementation
	|--------------------------------------------------------------------------
	*/

	public function offsetSet($where, $message)
	{
		$this->add($message, $where);
	}

	public function offsetExists($where)
	{
		list($bag, $type) = $this->getBagType($where);

		if (($bag == 'default') && ($type == 'msg')) { return TRUE; }

		return isset($this->messages[$bag][$type]);
	}

	public function offsetUnset($where)
	{
		list($bag, $type) = $this->getBagType($where);

		if (isset($this->messages[$bag][$type]))
		{
			unset($this->messages[$bag][$type]);
		}
	}

	public function offsetGet($where)
	{
		return $this->render($where);
	}
}
