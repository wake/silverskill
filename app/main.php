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
      ->order_by_desc ('id')
      ->find_array ();

    return $app
      ->assign ('event', $event)
      ->render ('index.html');

  })->bind ('index');


  /**
   *
   * record
   *
   */
  $app->get ('/record', function (Request $request) use ($app) {

    return $app
      ->render ('record.html');

  })->bind ('record');


  /**
   *
   * howto
   *
   */
  $app->get ('/howto', function (Request $request) use ($app) {

    return $app
      ->render ('howto.html');

  })->bind ('howto');


  /**
   *
   * signup
   *
   */
  $app->post ('/signup', function (Request $request) use ($app) {

    $data = [
      'title'    => $request->request->get ('title'),
      'location' => $request->request->get ('location'),
      'desc'     => $request->request->get ('desc'),
      'photo'    => $request->files->get ('photo'),
      'avatar'   => $request->files->get ('avatar'),
    ];

    $data['photo']  = $app['file.upload'] ($data['photo'], _UPLOAD . '/event');
    $data['avatar'] = $app['file.upload'] ($data['avatar'], _UPLOAD . '/event');

    $event = Model::factory ('Event')->create ();

    $event->title      = $data['title'];
    $event->location   = $data['location'];
    $event->desc       = $data['desc'];
    $event->state      = 1;
    $event->photo      = $data['photo'];
    $event->avatar     = $data['avatar'];
    $event->createdate = date ('Y-m-d H:i:s');
    $event->save ();

    return $app->redirect ($app->url ('index') .'#list');

  })->bind ('POST:signup');


  /**
   *
   * signup
   *
   */
  $app->post ('/request/{id}', function (Request $request, $id) use ($app) {

    $data = [
      'name'  => $request->request->get ('name'),
      'email' => $request->request->get ('email'),
    ];

    $req = Model::factory ('Request')->create ();

    $req->eid        = $id;
    $req->name       = $data['name'];
    $req->email      = $data['email'];
    $req->createdate = date ('Y-m-d H:i:s');
    $req->save ();

    return 'ok';

  })->bind ('POST:request');
