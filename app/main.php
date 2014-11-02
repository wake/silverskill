<?php

  use Silex\Application;
  use Symfony\Component\Validator\Validation;
  use Symfony\Component\Validator\Constraints as Assert;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpKernel\HttpKernelInterface;


  /**
   *
   * Home
   *
   */
  $app->get ('/', function (Request $request) use ($app) {

    $event = Model::factory ('Event')
      ->where ('state', 1)
      ->find_array ();

    return $app
      ->assign ('event', $event)
      ->render ('index.html');

  })->bind ('index');
