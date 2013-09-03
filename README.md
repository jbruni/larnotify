Installation
============

Use [composer](http://getcomposer.org), at the root of your laravel project:

    composer require jbruni/larnotify

And then add the service provider to the `providers` array at `app/config/app.php`:

    'Jbruni\Larnotify\NotificationServiceProvider',

It is recommended to add an alias to the `aliases` array at the same file:

    'Larnotify' => 'Jbruni\Larnotify\Larnotify',

<a id="usage"></a>
Usage
=====

### Basic

Basically, you add notifications by using the `add` method, from anywhere in your application:

    Larnotify::add('Settings saved.');

And anywhere in your views you can generate output:

    {{ $messages }}

The `add` method accepts an optional first parameter which can specify the message type:

    Larnotify::add('warning', 'You have only 1 credit remaining.');
    Larnotify::add('error', 'Failed to get the selected item.');

And to render the specific message type:

    {{ $messages['warning'] }}

You can also namespace your messages. For example, in Facebook you have "friend requests", "messages", and "notifications" - three distinct block of notifications...

    Larnotify::add('requests.unread', 'John wants to be your friend.');
    Larnotify::add('requests.read', 'Mary wants to be your friend.');
    Larnotify::add('messages.new', 'Hi! How are you?');
    Larnotify::add('notifications.liked', 'Pete liked your post.');

And where it is time to output...

    {{ $messages['requests.ALL'] }}
    {{ $messages['messages.new'] }}

In fact, EVERY message has a **namespace** and a **message type**, even when not specified.

The default namespace is "default" and the default message type is "msg".

So, in the first two examples (above), the messages were stored at "default.msg" and "default.warning" namespaces/types.

### Formatting output

There are two special message types: "sprintf" and "view":

    Larnotify::add('sprintf:Your name is %s.', 'John Doe');
    Larnotify::add('view:paused_service', array('date' => '10/10/2013'));

The first example is rendered using "sprintf" command. The second argument needs to be an array of the remaining "sprintf" parameters, or a single string.

The "view" type allows you to specify a **view** name, and its parameters.

Both message types accept a namespace.

    Larnotify::add('info.sprintf:Hello, %s! You have earned %s points.', array('Mary Jane', '100'));

The above notification will be stored at "info.sprintf" namespace/type and will be rendered using:

    sprintf('Hello, %s! You have earned %s points.', 'Mary Jane', '100');

Now, an example using `view`:

    Larnotify::add('info.view:user.balance', array('amount' => '108.90');

This will available at "info.view" and will be rendered using:

    View::make('user.balance', array('amount' => '108.90'));

Both shall be rendered at once, if called at the same request, since they belong to the same namespace:

    {{ $messages['info'] }}

If you want to select one of the types:

    {{ $messages['info.sprintf'] }}
    {{ $messages['info.view'] }}

#### Group templates

As we've seen, these "view" and "sprintf" templates are specified for single notifications.

It is possible to specify a single template which will render all its assigned notifications.

Example:

    Larnotify::add('user.info', 'Account successfully created.');
    Larnotify::add('user.info', 'You have earned 200 bonus points.');

Through configuration, either at the config file or at runtime, you can assign a **view** to render them:

    // config
    'views' => array(
        'user.info' => 'infowidget'
    );

    //runtime
    Larnotify::setView('user.info', 'infowidget');

(NOTE: If a "user.info" view exists, it will be automatically used. No configuration or "setView" needed.)

An array with the corresponding notifications will be available at the `$notifications` variable for the 'infowidget' view.

You can loop through them and render as you want.

The result will be available at

    {{ $messages['user.info] }}

Note that nothing prevents you from sending arrays instead of strings as messages. This allows you to further process the notifications in your view:

    Larnotify::add('commits.latest', array('repository_name' => 'Larnotify', 'hashes' => array('af12ca72', 'b7m2o018', 'abcdef78')));
    Larnotify::add(array('author' => 'Taylor', 'tweet' => 'Well done!'));

#### JSON

Instead of rendering HTML and including it in a template, or sending it as an AJAX response to be inserted into the DOM, you may be already dealing with a robust front-end application, using Angular.JS or similar, and you just want raw data, because you will be doing all the DOM magic through client-side Javascript...

In this or any other case, you can have the messages, or the template data, with no rendering, in JSON format:

    Larnotify::getJson('requests.all');

Or in a template:

    {{ $messages->getJson('user.info'); }}

You now start to think and feel that Larnotify can be used far beyond notifications... don't you?

Configuration
=============

Here is the current config file:

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

If no template is available, the `default_template` will be used as sprintf argument, receiving the message, the namespace, and the message type.

So, out of the box, `Larnotify::add('You won!');` renders as:

    <p class="default msg">You won!</p>

You may want to tackle directly with CSS, instead of anything else.

-----

The global variable available in all views to access Larnotify is `$messages` but you can change it through `view_share` configuration option.

Similarly, the messages will be available for rendering group templates as `$notifications`, but you can change it through `msg_variable` configuration option.

Finally, the `block_splitter` string is included when rendering, between each block rendered by Larnotify. I will always use "\n", so when looking at generated HTML source, each notification starts in a new line. But this may not be the desired behaviour, so this "magic" is turned off by default.

We have already covered the `views` configuration option in the "Group Templates" section. It is an array, where the key is the message type with or without a prefixed namespace, and the value is a view template name.

#### Thank you.
