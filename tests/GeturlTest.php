<?php

require_once dirname(__FILE__) . '/AbstractKleinTest.php';

class GeturlTest extends AbstractKleinTest {

	protected function setUp() {
		parent::setUp();

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
	}

	public function testgetUrlPlaceHolders() {
		$expect = "";

		respondExt('home', 'GET|POST /', function(){});
		respondExt('GET /users/', function(){});
		respondExt('users_show', 'GET /users/[i:id]', function(){});
		respondExt('posts_do', 'GET /posts/[create|edit:action]?/[i:id]?', function(){});

		echo geturlExt('home', true); echo "\n";
		$expect .= "/" . "\n";
		echo geturlExt('users_show', array('id' => 14), true); echo "\n";
		$expect .= "/users/14" . "\n";
		echo geturlExt('users_show', array(), true); echo "\n";
		$expect .= "/users/[:id]" . "\n";
		echo geturlExt('users_show', true); echo "\n";
		$expect .= "/users/[:id]" . "\n";
		echo geturlExt('posts_do', array('action' => 'edit', 'id' => 15), true); echo "\n";
		$expect .= "/posts/edit/15" . "\n";
		echo geturlExt('posts_do', array('id' => 15), true); echo "\n";
		$expect .= "/posts/[:action]/15" . "\n";
		echo geturlExt('posts_do', array('action' => "edit"), true); echo "\n";
		$expect .= "/posts/edit/[:id]" . "\n";
		$this->expectOutputString( $expect );
	}


	public function testPlaceHoldersException1() {
		$this->setExpectedException('OutOfRangeException', "does not exist");

		respondExt('users', 'GET /users/[i:id]/[:action]', function(){});

		echo geturlExt('notset');
	}

	public function testPlaceHoldersException2() {
		$this->setExpectedException('InvalidArgumentException', "not set for route");

		respondExt('users', 'GET /users/[i:id]/[:action]', function(){});

		echo geturlExt('users', array('id' => "10"));
	}

}
