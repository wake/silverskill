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
  $app->get ('/admin/', function (Request $request) use ($app) {

    return $app->redirect ($app->url ('admin/event/list'));

  })->bind ('admin/index');
