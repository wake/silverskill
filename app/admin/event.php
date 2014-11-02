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
  $app->get ('/admin/event/', function (Request $request) use ($app) {

    $event = Model::factory ('Event')
      ->find_array ();

    return $app
      ->assign ('event', $event)
      ->render ('admin/event/list.html');

  })->bind ('admin/event/list');


  /**
   *
   *
   *
   */
  $app->get ('/admin/event/add', function (Request $request) use ($app) {

    return $app
      ->render ('admin/event/edit.html');

  })->bind ('admin/event/add');


  /**
   *
   *
   *
   */
  $app->post ('/admin/event/add', function (Request $request) use ($app) {

    $data = [
      'title'    => $request->request->get ('title'),
      'location' => $request->request->get ('location'),
      'state'    => $request->request->get ('state'),
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
    $event->state      = $data['state'];
    $event->photo      = $data['photo'];
    $event->avatar     = $data['avatar'];
    $event->createdate = date ('Y-m-d H:i:s');
    $event->save ();

    return $app->redirect ($app->url ('admin/event/list'));

  })->bind ('POST:admin/event/add');


  /**
   *
   *
   *
   */
  $app->get ('/admin/event/edit/{id}', function (Request $request, $id) use ($app) {

    $event = Model::factory ('Event')->find_one ($id);

    return $app
      ->assign ('id', $id)
      ->assign ('form', ['data' => $event->as_array ()])
      ->render ('admin/event/edit.html');

  })->bind ('admin/event/edit');


  /**
   *
   *
   *
   */
  $app->post ('/admin/event/edit/{id}', function (Request $request, $id) use ($app) {

    $data = [
      'title'    => $request->request->get ('title'),
      'location' => $request->request->get ('location'),
      'desc'     => $request->request->get ('desc'),
      'state'    => $request->request->get ('state'),
      'photo'    => $request->files->get ('photo'),
      'avatar'   => $request->files->get ('avatar'),
    ];

    $data['photo']  = $app['file.upload'] ($data['photo'], _UPLOAD . '/event');
    $data['avatar'] = $app['file.upload'] ($data['avatar'], _UPLOAD . '/event');

    $event = Model::factory ('Event')
      ->find_one ($id);

    $event->title    = $data['title'];
    $event->location = $data['location'];
    $event->state    = $data['state'];
    $event->desc     = $data['desc'];

    if ($data['photo'] && $data['photo'] != '')
      $event->photo = $data['photo'];

    if ($data['avatar'] && $data['avatar'] != '')
      $event->avatar = $data['avatar'];

    $event->save ();

    return $app->redirect ($app->url ('admin/event/list'));

  })->bind ('POST:admin/event/edit');
