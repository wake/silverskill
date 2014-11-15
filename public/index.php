<?php

  use Silex\Application;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpKernel\HttpKernelInterface;
  use Symfony\Component\Yaml\Yaml;


  /**
   *
   * Root
   *
   */
  define ('_ROOT', dirname (__DIR__));


  /**
   *
   * Upload
   *
   */
  define ('_UPLOAD', _ROOT .'/public/upload');


  /**
   *
   * Vendors
   *
   */
  require_once _ROOT . '/vendor/autoload.php';


  /**
   *
   * Environments and Defines
   *
   */
  $env = Yaml::parse (_ROOT . '/env.yml');

  define ('_HOST',       'http://' . $env['web']['host']);
  define ('_BASE',       rtrim (dirname ($_SERVER['SCRIPT_NAME']), '/'));
  define ('_HTTP',       _HOST . _BASE);
  define ('_URI',        str_replace ('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));

  define ('_TIMESTAMP',  time ());
  define ('_DATE',       date ('Y-m-d', _TIMESTAMP));
  define ('_TIME',       date ('H:i:s', _TIMESTAMP));
  define ('_DATETIME',   _DATE .' '. _TIME);

  define ('_SECRET',     'sIlVeRsKiLl');


  /**
   *
   * Externals (before $app generate)
   *
   */
  //require_once _ROOT . '/external/I18n.php';
  require_once _ROOT . '/external/TwigTraitAdapter.php';
  require_once _ROOT . '/external/Pager.php';


  /**
   *
   * Initial application
   *
   */

  class _Application extends Silex\Application {

    use TwigTraitAdapter;
    use Silex\Application\UrlGeneratorTrait;
    use Silex\Application\FormTrait;
    use Silex\Application\SwiftmailerTrait;
    /*
    use Silex\Application\SecurityTrait;
    use Silex\Application\FormTrait;
    use Silex\Application\MonologTrait;
    use Silex\Application\TranslationTrait;
    */
  }

  $app = new _Application ();


  /**
   *
   * Vendors & Externals (after $app generate)
   *
   */
  require_once _ROOT . '/external/StructureLoader.php';
  require_once _ROOT . '/external/Validation.php';
  require_once _ROOT . '/external/Forward.php';
  require_once _ROOT . '/external/File.php';
  require_once _ROOT . '/external/Bower.php';


  /**
   *
   * Application evironments
   *
   */
  $app['env'] = $env;


  /**
   *
   * Component file
   *
   */
  $bower = [];

  if (file_exists (_ROOT . '/component.json'))
    $bower = json_decode (file_get_contents (_ROOT . '/component.json'), true);

  $app['bower'] = $bower;


  /**
   *
   * Debug mode
   *
   */
  $sys = $env['system'];

  if (isset ($_GET['bug']) && (isset ($sys['debug']) && $sys['debug'] == 1))
    $app['debug'] = true;

  if (isset ($sys['force_debug']) && $sys['force_debug'] == 1)
    $app['debug'] = true;


  /**
   *
   * Register services
   *
   */

  // Seervice Controller Provider
  $app->register (new Silex\Provider\ServiceControllerServiceProvider ());

  // Url Generator
  $app->register (new Silex\Provider\UrlGeneratorServiceProvider ());

  // Validator
  $app->register(new Silex\Provider\ValidatorServiceProvider ());

  // Form
  $app->register(new Silex\Provider\FormServiceProvider ());

  // Session
  $app->register(new Silex\Provider\SessionServiceProvider ());

  $app['session']->registerBag (new Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag ());

  // Twig
  $app->register (new Silex\Provider\TwigServiceProvider (), array (
    'twig.path'    => _ROOT . '/view',
    'twig.options' => array ('cache' => _ROOT . '/caches', 'auto_reload' => true),
  ));

  $app['twig']->addExtension (new Twig_Extensions_Extension_I18n ());
  $app['twig']->addFunction (new Twig_SimpleFunction ('die', 'die'));
  $app['twig']->addFunction (new Twig_SimpleFunction ('set', function ($key, $val) use ($app) { $app['twig.' . $key] = $val; }));
  $app['twig']->addFilter (new Twig_SimpleFilter ('is_*', function ($page, $input, $ret = 'active') { return $input == $page ? $ret : ''; }));
  $app['twig']->addFilter (new Twig_SimpleFilter ('*_of_*', function ($type, $field, $form, $template = '') use ($app) {

    $violation = (isset ($form['violation']) && isset ($form['violation'][$field])) ? $form['violation'][$field] : [];
    $value = (isset ($form['data']) && isset ($form['data'][$field])) ? $form['data'][$field] : '';

    $result = '';

    if ($type == 'val' || $type == 'value') {

      if (isset ($violation['value']))
        $result = $violation['value'];

      $result = $value;
    }

    else if (($type == 'err' || $type == 'error') && isset ($violation['message']))
      $result = 'error';

    else if (($type == 'msg' || $type == 'message') && isset ($violation['message'])) {
      $template =
        $template == ''
          ? (! isset ($app['twig.form.template'])
            ? '<span class="tw_add_member_input_error">%s</span>'
            : $app['twig.form.template'])
          : $template;
      $result = $violation['message'];

      if (strlen ($template) > 0)
        return vsprintf ($template, $result);
    }

    return $result;

  }, ['is_safe' => ['all']]));


  /**
   *
   * Boot registered services
   *
   */
  $app->boot ();


  /**
   *
   * Paris & Idiorm configurations
   *
   */
  $database = $app['env']['database'];

  ORM::configure ('mysql:host='. $database['host'] .';dbname='. $database['database']);
  ORM::configure ('username', $database['account']);
  ORM::configure ('password', $database['password']);

  ORM::configure ('logging',  $database['logging']);
  ORM::configure ('caching',  $database['caching']);
  ORM::configure ('driver_options', array (PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));


  /**
   *
   * Load database models
   *
   */
  $app['StructureLoader'] (['event', 'request'], _ROOT . '/model');


  /**
   *
   * Load Controllers
   *
   */
  $app['StructureLoader'] (['main', 'admin' => ['main', 'event', 'request']], _ROOT . '/app');


  /**
   *
   * Asset url
   *
   */
  $app->before (function (Symfony\Component\HttpFoundation\Request $request) use ($app) {

    /*
    $hash = $app['bower.asset.hash'] ($app['bower']);

    $app
      ->assign ('asset_js_url',  $app->url ('asset/generate', ['hash' => $hash['js'],  'type' => 'js']))
      ->assign ('asset_css_url', $app->url ('asset/generate', ['hash' => $hash['css'], 'type' => 'css']))
      ;
    */

  }, 1);


  /**
   *
   * Form Helper
   *
   */
  $app->before (function (Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $route = $request->get ('_route');
    $route = explode ('/', $route);
    $operate = end ($route);

    if ($operate == 'add' || $operate == 'create')
      $app->assign ('operate', $operate);

    else if ($operate == 'edit' || $operate == 'update')
      $app->assign ('operate', $operate);

    else
      $app->assign ('operate', '');

  }, 10);


  /**
   *
   * Forward helper
   *
   */
  $app->before (function (Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $app->assign ('form', []);

    if ($form = $request->attributes->get ('form'))
      $app->assign ('form', $form);

  }, 20);



  /**
   *
   * execute
   *
   */
  $app->run ();

  /*
  if (isset ($_GET['bug']) && $app['debug'])
    echo '<pre>' . var_export (ORM::get_query_log (), true);

  else if ($app['debug'])
    echo "<!-- \n" . var_export (ORM::get_query_log (), true) . "\n -->";
  */