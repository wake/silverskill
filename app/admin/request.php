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
  $app->get ('/admin/request/', function (Request $request) use ($app) {

    $request = Model::factory ('Request')
      ->select ('request.*')
      ->select ('event.title')
      ->join ('event', ['request.eid', '=', 'event.id'])
      ->find_array ();

    return $app
      ->assign ('request', $request)
      ->render ('admin/request/list.html');

  })->bind ('admin/request/list');


  /**
   *
   *
   *
   */
  $app->get ('/admin/request/add', function (Request $request) use ($app) {

    $event = Model::factory ('Event')
      ->where ('state', 1)
      ->find_array ();

    return $app
      ->assign ('event', $event)
      ->render ('admin/request/edit.html');

  })->bind ('admin/request/add');


  /**
   *
   *
   *
   */
  $app->post ('/admin/request/add', function (Request $request) use ($app) {

    $data = [
      'eid'      => $request->request->get ('eid'),
      'email'    => $request->request->get ('email'),
      'date'     => $request->request->get ('date'),
      'question' => $request->request->get ('question'),
    ];

    $request = Model::factory ('Request')->create ();

    $request->eid        = $data['eid'];
    $request->email      = $data['email'];
    $request->date       = $data['date'];
    $request->question   = $data['question'];
    $request->createdate = date ('Y-m-d H:i:s');
    $request->save ();

    return $app->redirect ($app->url ('admin/request/list'));

  })->bind ('POST:admin/request/add');


  /**
   *
   *
   *
   */
  $app->get ('/admin/request/edit/{id}', function (Request $request, $id) use ($app) {

    $request = Model::factory ('Request')
      ->find_one ($id);

    $event = Model::factory ('Event')
      ->where ('state', 1)
      ->find_array ();

    return $app
      ->assign ('id', $id)
      ->assign ('event', $event)
      ->assign ('form', ['data' => $request->as_array ()])
      ->render ('admin/request/edit.html');

  })->bind ('admin/request/edit');


  /**
   *
   *
   *
   */
  $app->post ('/admin/request/edit/{id}', function (Request $request, $id) use ($app) {

    $data = [
      'title'    => $request->request->get ('title'),
      'location' => $request->request->get ('location'),
      'desc'     => $request->request->get ('desc'),
      'state'    => $request->request->get ('state'),
      'photo'    => $request->files->get ('photo'),
      'avatar'   => $request->files->get ('avatar'),
    ];

    $data['photo']  = $app['file.upload'] ($data['photo'], _UPLOAD . '/request');
    $data['avatar'] = $app['file.upload'] ($data['avatar'], _UPLOAD . '/request');

    $request = Model::factory ('Request')
      ->find_one ($id);

    $request->title    = $data['title'];
    $request->location = $data['location'];
    $request->state    = $data['state'];
    $request->desc     = $data['desc'];

    if ($data['photo'] && $data['photo'] != '')
      $request->photo = $data['photo'];

    if ($data['avatar'] && $data['avatar'] != '')
      $request->avatar = $data['avatar'];

    $request->save ();

    return $app->redirect ($app->url ('admin/request/list'));

  })->bind ('POST:admin/request/edit');
