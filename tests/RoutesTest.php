<?php

require_once dirname(__FILE__) . '/setup.php';

class TestClass {
	static function GET($r, $r, $a) {
		echo 'ok';
	}
}

class RoutesTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		Klein\reset();

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['PHPUNIT'] = true;
	}

	protected function assertOutputSame($expected, $callback, $message = '') {
	    ob_start();
	    call_user_func($callback);
	    $out = ob_get_contents();
	    ob_end_clean();
	    $this->assertSame($expected, $out, $message);
	}
	protected function assertOutputContains($expected, $callback, $message = '') {
	    ob_start();
	    call_user_func($callback);
	    $out = ob_get_contents();
	    ob_end_clean();
	    $this->assertContains($expected, $out, $message);
	}

	public function testBasic() {
		$this->expectOutputString( 'x' );

		Klein\respond( '/', function(){ echo 'x'; });
		Klein\respond( '/something', function(){ echo 'y'; });
		Klein\dispatch( '/' );
	}

	public function testCallable() {
		$this->expectOutputString( 'okok' );
		Klein\respond( '/', array('TestClass', 'GET'));
		Klein\respond( '/', 'TestClass::GET');
		Klein\dispatch( '/' );
	}

	public function testAppReference() {
		$this->expectOutputString( 'ab' );
		Klein\respond( '/', function($r, $r ,$a){ $a->state = 'a'; });
		Klein\respond( '/', function($r, $r ,$a){ $a->state .= 'b'; });
		Klein\respond( '/', function($r, $r ,$a){ print $a->state; });
		Klein\dispatch( '/' );
	}

	public function testCatchallImplicit() {
		$this->expectOutputString( 'b' );

		Klein\respond( '/one', function(){ echo 'a'; });
		Klein\respond( function(){ echo 'b'; });
		Klein\respond( '/two', function(){ } );
		Klein\respond( '/three', function(){ echo 'c'; } );
		Klein\dispatch( '/two' );
	}

	public function testCatchallAsterisk() {
		$this->expectOutputString( 'b' );

		Klein\respond( '/one', function(){ echo 'a'; } );
		Klein\respond( '*', function(){ echo 'b'; } );
		Klein\respond( '/two', function(){ } );
		Klein\respond( '/three', function(){ echo 'c'; } );
		Klein\dispatch( '/two' );
	}

	public function testCatchallImplicitTriggers404() {
		$this->expectOutputString("b404\n");

		Klein\respond( function(){ echo 'b'; });
		Klein\respond( 404, function(){ echo "404\n"; } );
		Klein\dispatch( '/' );
	}

	public function testRegex() {
		$this->expectOutputString( 'z' );

		Klein\respond( '@/bar', function(){ echo 'z'; });
		Klein\dispatch( '/bar' );
	}

	public function testRegexNegate() {
		$this->expectOutputString( "y" );

		Klein\respond( '!@/foo', function(){ echo 'y'; });
		Klein\dispatch( '/bar' );
	}

	public function test404() {
		$this->expectOutputString("404\n");

		Klein\respond( '/', function(){ echo 'a'; } );
		Klein\respond( 404, function(){ echo "404\n"; } );
		Klein\dispatch( '/foo' );
	}

	public function testParamsBasic() {
		$this->expectOutputString( 'blue' );

		Klein\respond( '/[:color]', function($request){ echo $request->param('color'); });
		Klein\dispatch( '/blue' );
	}

	public function testParamsIntegerSuccess() {
		$this->expectOutputString( "string(3) \"987\"\n" );

		Klein\respond( '/[i:age]', function($request){ var_dump( $request->param('age') ); });
		Klein\dispatch( '/987' );
	}

	public function testParamsIntegerFail() {
		$this->expectOutputString( '404 Code' );

		Klein\respond( '/[i:age]', function($request){ var_dump( $request->param('age') ); });
		Klein\respond( '404', function(){ echo '404 Code'; } );
		Klein\dispatch( '/blue' );
	}

	public function test404TriggersOnce() {
		$this->expectOutputString( 'd404 Code' );

		Klein\respond( function(){ echo "d"; } );
		Klein\respond( '404', function(){ echo '404 Code'; } );
		Klein\dispatch( '/notroute' );
	}

	public function testStarRouteTriggers404() {
		$this->expectOutputString( 'c404 Code' );

		Klein\respond( '*', function(){ echo 'c'; });
		Klein\respond( '404', function(){ echo '404 Code'; } );
		Klein\dispatch( '/notroute' );
	}

	public function testNullRouteTriggers404() {
		$this->expectOutputString( 'c404 Code' );

		Klein\respond( function(){ echo 'c'; });
		Klein\respond( '404', function(){ echo '404 Code'; } );
		Klein\dispatch( '/notroute' );
	}

	public function testMethodSingle() {
		$this->expectOutputString( 'd' );

		Klein\respond( "GET",  "/a", function(){ echo 'd'; });
		Klein\respond( "POST", "/a", function(){ echo 'e'; });
		Klein\dispatch( '/a' );
	}

	public function testMethodMultiple() {
		$this->expectOutputString( 'd' );

		Klein\respond( "GET|POST",  "/a", function(){ echo 'd'; });
		Klein\dispatch( '/a' );
	}

	public function testgetUrl() {
		$expect = "";

		Klein\respond('home', 'GET|POST','/', function(){});
		Klein\respond('GET','/users/', function(){});
		Klein\respond('users_show', 'GET','/users/[i:id]', function(){});
		Klein\respond('users_do', 'POST','/users/[i:id]/[delete|update:action]', function(){});
		Klein\respond('posts_do', 'GET', '/posts/[create|edit:action]?/[i:id]?', function(){});

		echo Klein\getUrl('home'); echo "\n";
		$expect .= "/" . "\n";
		echo Klein\getUrl('users_show', array('id' => 14)); echo "\n";
		$expect .= "/users/14" . "\n";
		echo Klein\getUrl('users_do', array('id' => 17, 'action'=>'delete')); echo "\n";
		$expect .= "/users/17/delete" . "\n";
		echo Klein\getUrl('posts_do', array('id' => 16)); echo "\n";
		$expect .= "/posts/16" . "\n";
		echo Klein\getUrl('posts_do', array('action' => 'edit', 'id' => 15)); echo "\n";
		$expect .= "/posts/edit/15" . "\n";
		$this->expectOutputString( $expect );
	}

	public function testOptsParam() {
		$this->expectOutputString( "action=,id=16" );
		Klein\respond('users_do', 'GET','/posts/[create|edit:action]?/[i:id]?', function($rq,$rs,$ap){echo "action=".$rq->param("action").",id=".$rq->param("id");});

		Klein\dispatch("/posts/16");
	}

	public function testgetUrlPlaceHolders() {
		$expect = "";

		Klein\respond('home', 'GET|POST','/', function(){});
		Klein\respond('GET','/users/', function(){});
		Klein\respond('users_show', 'GET','/users/[i:id]', function(){});
		Klein\respond('posts_do', 'GET', '/posts/[create|edit:action]?/[i:id]?', function(){});

		echo Klein\getUrl('home', true); echo "\n";
		$expect .= "/" . "\n";
		echo Klein\getUrl('users_show', array('id' => 14), true); echo "\n";
		$expect .= "/users/14" . "\n";
		echo Klein\getUrl('users_show', array(), true); echo "\n";
		$expect .= "/users/[:id]" . "\n";
		echo Klein\getUrl('users_show', true); echo "\n";
		$expect .= "/users/[:id]" . "\n";
		echo Klein\getUrl('posts_do', array('action' => 'edit', 'id' => 15), true); echo "\n";
		$expect .= "/posts/edit/15" . "\n";
		echo Klein\getUrl('posts_do', array('id' => 15), true); echo "\n";
		$expect .= "/posts/[:action]/15" . "\n";
		echo Klein\getUrl('posts_do', array('action' => "edit"), true); echo "\n";
		$expect .= "/posts/edit/[:id]" . "\n";
		$this->expectOutputString( $expect );
	}


	public function testPlaceHoldersException1() {
		$this->setExpectedException('OutOfRangeException', "does not exist");

		Klein\respond('users', 'GET','/users/[i:id]/[:action]', function(){});

		echo Klein\getUrl('notset');
	}

	public function testPlaceHoldersException2() {
		$this->setExpectedException('InvalidArgumentException', "not set for route");

		Klein\respond('users', 'GET','/users/[i:id]/[:action]', function(){});

		echo Klein\getUrl('users', array('id' => "10"));
	}

	public function testMethodCatchAll() {
		$this->expectOutputString( 'yup!123' );

		Klein\respond( 'POST', null, function($request){ echo 'yup!'; });
		Klein\respond( 'POST', '*', function($request){ echo '1'; });
		Klein\respond( 'POST', '/', function($request){ echo '2'; });
		Klein\respond( function($request){ echo '3'; });
		Klein\dispatch( '/', 'POST' );
	}

	public function testLazyTrailingMatch() {
		$this->expectOutputString( 'this-is-a-title-123' );

		Klein\respond( '/posts/[*:title][i:id]', function($request){
			echo $request->param('title')
				. $request->param('id');
		});
		Klein\dispatch( '/posts/this-is-a-title-123' );
	}

	public function testFormatMatch() {
		$this->expectOutputString( 'xml' );

		Klein\respond( '/output.[xml|json:format]', function($request){
			echo $request->param('format');
		});
		Klein\dispatch( '/output.xml' );
	}

	public function testControllerActionStyleRouteMatch() {
		$this->expectOutputString( 'donkey-kick' );

		Klein\respond( '/[:controller]?/[:action]?', function($request){
			echo $request->param('controller')
				. '-' . $request->param('action');
		});
		Klein\dispatch( '/donkey/kick' );
	}

	public function testRespondArgumentOrder() {
		$this->expectOutputString( 'abcdef' );

		Klein\respond( function(){ echo 'a'; });
		Klein\respond( null, function(){ echo 'b'; });
		Klein\respond( '/endpoint', function(){ echo 'c'; });
		Klein\respond( 'GET', null, function(){ echo 'd'; });
		Klein\respond( array( 'GET', 'POST' ), null, function(){ echo 'e'; });
		Klein\respond( array( 'GET', 'POST' ), '/endpoint', function(){ echo 'f'; });
		Klein\dispatch( '/endpoint' );
	}

	public function testTrailingMatch() {
		Klein\respond( '/?[*:trailing]/dog/?', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('/cat/dog'); });
		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('/cat/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('/cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('/cat/ball/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ Klein\dispatch('cat/ball/cheese/dog'); });
	}

	public function testTrailingPossessiveMatch() {
		respond( '/sub-dir/[**:trailing]', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ dispatch('/sub-dir/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatch('/sub-dir/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatch('/sub-dir/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ dispatch('/sub-dir/ball/cheese/dog'); });
	}

	public function testNSDispatch() {
		Klein\with('/u', function () {
			Klein\respond('GET', '/?',     function ($request, $response) { echo "slash";   });
			Klein\respond('GET', '/[:id]', function ($request, $response) { echo "id"; });
		});
		Klein\respond(404, function ($request, $response) { echo "404"; });

		$this->assertOutputSame("slash",          function(){Klein\dispatch("/u");});
		$this->assertOutputSame("slash",          function(){Klein\dispatch("/u/");});
		$this->assertOutputSame("id",             function(){Klein\dispatch("/u/35");});
		$this->assertOutputSame("404",             function(){Klein\dispatch("/35");});
	}

	public function test405Routes() {
		$resultArray = array();

		$this->expectOutputString( '_' );

		Klein\respond( function(){ echo '_'; });
		Klein\respond( 'GET', null, function(){ echo 'fail'; });
		Klein\respond( array( 'GET', 'POST' ), null, function(){ echo 'fail'; });
		Klein\respond( 405, function($a,$b,$c,$d,$methods) use ( &$resultArray ) {
			$resultArray = $methods;
		});
		Klein\dispatch( '/sure', 'DELETE' );

		$this->assertCount( 2, $resultArray );
		$this->assertContains( 'GET', $resultArray );
		$this->assertContains( 'POST', $resultArray );
	}

	public function testDot1() {
		$this->expectOutputString( 'matchA:slug=ABCD_E--matchB:slug=ABCD_E--' );

		Klein\respond('/[*:cpath]/[:slug].[:format]',   function($rq){ echo 'matchA:slug='.$rq->param("slug").'--';});
		Klein\respond('/[*:cpath]/[:slug].[:format]?',  function($rq){ echo 'matchB:slug='.$rq->param("slug").'--';});
		Klein\respond('/[*:cpath]/[a:slug].[:format]?', function($rq){ echo 'matchC:slug='.$rq->param("slug").'--';});
		Klein\dispatch("/category1/categoryX/ABCD_E.php");
	}

	public function testDot2() {
		$this->expectOutputString( 'matchB:slug=ABCD_E--' );

		Klein\respond('/[*:cpath]/[:slug].[:format]',   function($rq){ echo 'matchA:slug='.$rq->param("slug").'--';});
		Klein\respond('/[*:cpath]/[:slug].[:format]?',  function($rq){ echo 'matchB:slug='.$rq->param("slug").'--';});
		Klein\respond('/[*:cpath]/[a:slug].[:format]?', function($rq){ echo 'matchC:slug='.$rq->param("slug").'--';});
		Klein\dispatch("/category1/categoryX/ABCD_E");
	}

}
