<?php

return array(

	/**
	 * string   View templates (add an entry for each message type)
	 * 
	 * NOTE: "ALL", "view" and "sprintf" are reserved message type names
	 */
	'views' => array(
		'default.msg' => '',
	),

	/**
	 * string   Global view variable name (Larnotify object)
	 */
	'view_share' => 'messages',

	/**
	 * string   Notifications variable name (Array of messages)
	 */
	'msg_variable' => 'notifications',

	/**
	 * string   Use this sprintf template to render messages if none is provided
	 */
	'default_template' => '<p class="%2$s %3$s">%1$s</p>',

	/**
	 * string   String to be included between each block of rendered output
	 * 
	 *  NOTE: "\n" is recommended
	 */
	'block_splitter' => '',

);
