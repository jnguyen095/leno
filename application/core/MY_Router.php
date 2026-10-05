<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI3's CI_Router::_set_default_controller() only ever parses
 * $route['default_controller'] as "class[/method]" — it has no support for a
 * "directory/class" target, unlike normal (non-default) routes which resolve
 * subdirectories fine via _validate_request(). Since the public-facing
 * homepage now lives at application/controllers/public/Public_site.php (see
 * [[064_allow_recipe_as_ingredient]] sibling refactor — Public/Menu/Trung_thu
 * moved into their own controllers/public + views/public folders), the empty
 * URI ("/") needs default_controller = 'public/public_site' to resolve into
 * that subdirectory. This override adds that support, mirroring how
 * _validate_request() already treats a first path segment that matches a
 * real controllers/ subdirectory.
 */
class MY_Router extends CI_Router
{
    protected function _set_default_controller()
    {
        if (empty($this->default_controller))
        {
            show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
        }

        $parts = explode('/', $this->default_controller);
        $method = 'index';

        if (count($parts) >= 2 && is_dir(APPPATH.'controllers/'.$parts[0]))
        {
            $this->set_directory(array_shift($parts));
        }

        $class = array_shift($parts);
        if ( ! empty($parts))
        {
            $method = array_shift($parts);
        }

        if ( ! file_exists(APPPATH.'controllers/'.$this->directory.ucfirst($class).'.php'))
        {
            // This will trigger 404 later
            return;
        }

        $this->set_class($class);
        $this->set_method($method);

        $this->uri->rsegments = array(1 => $class, 2 => $method);

        log_message('debug', 'No URI present. Default controller set.');
    }
}
