<?php namespace Jbruni\Larnotify;

use Illuminate\Support\Facades\Facade;

class Larnotify extends Facade {

	protected static function getFacadeAccessor() { return 'larnotify'; }

}
