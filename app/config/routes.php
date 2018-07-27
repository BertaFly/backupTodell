<?php

$app->get('/', 'HomeController:index')->setName('home');

// $app->posr('/auth/signin', 'AuthController:signIn');

$app->post('/auth/signin', 'AuthController:postSignIn');
$app->post('/auth/signup', 'AuthController:postSignUp');
$app->post('/auth/reset', 'AuthController:postResetPass');

$app->get('/auth/confirmRegistration', 'AuthController:getConfirmRegistr');
$app->get('/auth/confirmRegistration', 'AuthController:confirmResetPass');


// $app->post('/auth/signup', 'AuthController:postSignUp');

// $app->post('/auth/signin', 'AuthController:postSignIn')->setName('auth.signin');
