<?php

$app->get('/', 'HomeController:index')->setName('home');

// $app->posr('/auth/signin', 'AuthController:signIn');

$app->post('/auth/signin', 'AuthController:postSignIn');
$app->post('/auth/signup', 'AuthController:postSignUp');
$app->post('/auth/reset', 'AuthController:postResetPass');

$app->get('/auth/confirmRegistration', 'AuthController:getConfirmRegistr');
$app->get('/auth/confirmResetPass', 'AuthController:confirmResetPass');
$app->post('/auth/logOut', 'AuthController:postLogOut');

$app->post('/user/isFull', 'UserController:postCheckProfileIsFull');
$app->post('/user/getAllInfo', 'UserController:postGetAllInfo');
$app->post('/user/getAllPhoto', 'UserController:postGetAllPhoto');

// $app->post('/user/getWhoLikes', 'UserController:postWhoLikesMe');
$app->post('/user/recordInfo', 'UserController:postRecordInfo');
$app->post('/user/recordAbout', 'UserController:postRecordAbout');
$app->post('/user/newPhoto', 'UserController:postNewPhoto');
$app->post('/user/delMyPic', 'UserController:postDelPhoto');
$app->post('/user/setAvatar', 'UserController:postSetAvatar');
$app->post('/user/getAbout', 'UserController:postForAbout');
$app->post('/user/dellTag', 'UserController:postDellTag');
$app->post('/user/pushCoord', 'UserController:postLocation');
$app->post('/user/getCoord', 'UserController:postReturnCoord');




// $app->post('/auth/signup', 'AuthController:postSignUp');

// $app->post('/auth/signin', 'AuthController:postSignIn')->setName('auth.signin');
