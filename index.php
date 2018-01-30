<?php
session_start();

require_once("vendor/autoload.php");

use Hcode\Page;
use Hcode\PageAdmin;
use Hcode\Model\User;

$app = new \Slim\Slim();

// $app->config('debug', true);

$app->get('/', function() {

	$page = new Page();

	$page->setTpl("index");

});

$app->get('/admin', function() {

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

$app->get('/admin/login', function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");
});

$app->post('/admin/login', function(){

	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;

});

$app->get('/admin/logout',function(){

	User::logout();

	header("Location: /admin/login");
	exit;

});

// ADMIN USUARIOS
$app->get('/admin/users',function(){

	User::verifyLogin();

	$user = new User;

	$page = new PageAdmin();

	$page->setTpl("users",[
		"users"=>User::listAll()
	]);
});

$app->get('/admin/users/create',function(){

	$page = new PageAdmin();

	$page->setTpl("users-create");
});

$app->get('/admin/users/update',function(){

	$page = new PageAdmin();

	$page->setTpl("users-update");
});

// Password recovery
$app->get('/admin/forgot', function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");
});

$app->post('/admin/forgot',function(){

	$user = User::getForgot($_POST['email']);

	header("Location: /admin/forgot/sent");
	exit;

});

$app->get('/admin/forgot/sent', function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");
});

$app->get('/admin/forgot/reset', function(){

  // Validates code for render in hidden input
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset",[
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	]);
});

$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setFogotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = User::getPasswordHash($_POST["password"]);

	$user->setPassword($password);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});


$app->run();

 ?>
