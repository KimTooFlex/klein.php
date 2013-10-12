<?php

require_once dirname(__FILE__) . '/AbstractKleinTest.php';

class TestExtClass {
	static function GET($r, $r, $a) {
		echo 'ok';
	}
}

class RoutesExtTest extends AbstractKleinTest {

	protected function setUp() {
		parent::setUp();

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
	}

	public function testBasic() {
		$this->expectOutputString( 'x' );

		respondExt( '/', function(){ echo 'x'; });
		respondExt( '/something', function(){ echo 'y'; });
		dispatchExt( '/' );
	}

	public function testCallable() {
		$this->expectOutputString( 'okok' );
		respondExt( '/', array('TestExtClass', 'GET'));
		respondExt( '/', 'TestExtClass::GET');
		dispatchExt( '/' );
	}

	public function testAppReference() {
		$this->expectOutputString( 'ab' );
		respondExt( '/', function($r, $r ,$a){ $a->state = 'a'; });
		respondExt( '/', function($r, $r ,$a){ $a->state .= 'b'; });
		respondExt( '/', function($r, $r ,$a){ print $a->state; });
		dispatchExt( '/' );
	}

	public function testCatchallImplicit() {
		$this->expectOutputString( 'b' );

		respondExt( '/one', function(){ echo 'a'; });
		respondExt( function(){ echo 'b'; });
		respondExt( '/two', function(){ } );
		respondExt( '/three', function(){ echo 'c'; } );
		dispatchExt( '/two' );
	}

	public function testCatchallAsterisk() {
		$this->expectOutputString( 'b' );

		respondExt( '/one', function(){ echo 'a'; } );
		respondExt( '*', function(){ echo 'b'; } );
		respondExt( '/two', function(){ } );
		respondExt( '/three', function(){ echo 'c'; } );
		dispatchExt( '/two' );
	}

	public function testCatchallImplicitTriggers404() {
		$this->expectOutputString("b404\n");

		respondExt( function(){ echo 'b'; });
		respondExt( 404, function(){ echo "404\n"; } );
		dispatchExt( '/' );
	}

	public function testRegex() {
		$this->expectOutputString( 'z' );

		respondExt( '@/bar', function(){ echo 'z'; });
		dispatchExt( '/bar' );
	}

	public function testRegexNegate() {
		$this->expectOutputString( "y" );

		respondExt( '!@/foo', function(){ echo 'y'; });
		dispatchExt( '/bar' );
	}

	public function test404() {
		$this->expectOutputString("404\n");

		respondExt( '/', function(){ echo 'a'; } );
		respondExt( 404, function(){ echo "404\n"; } );
		dispatchExt( '/foo' );
	}

	public function testParamsBasic() {
		$this->expectOutputString( 'blue' );

		respondExt( '/[:color]', function($request){ echo $request->param('color'); });
		dispatchExt( '/blue' );
	}

	public function testParamsIntegerSuccess() {
		$this->expectOutputString( "string(3) \"987\"\n" );

		respondExt( '/[i:age]', function($request){ var_dump( $request->param('age') ); });
		dispatchExt( '/987' );
	}

	public function testParamsIntegerFail() {
		$this->expectOutputString( '404 Code' );

		respondExt( '/[i:age]', function($request){ var_dump( $request->param('age') ); });
		respondExt( '404', function(){ echo '404 Code'; } );
		dispatchExt( '/blue' );
	}

	public function testParamsAlphaNum() {
		respondExt( '/[a:audible]', function($request){ echo $request->param('audible'); });

		$this->assertOutputSame( 'blue42',  function(){ dispatchExt('/blue42'); });
		$this->assertOutputSame( '',        function(){ dispatchExt('/texas-29'); });
		$this->assertOutputSame( '',        function(){ dispatchExt('/texas29!'); });
	}

	public function testParamsHex() {
		respondExt( '/[h:hexcolor]', function($request){ echo $request->param('hexcolor'); });

		$this->assertOutputSame( '00f',     function(){ dispatchExt('/00f'); });
		$this->assertOutputSame( 'abc123',  function(){ dispatchExt('/abc123'); });
		$this->assertOutputSame( '',        function(){ dispatchExt('/876zih'); });
		$this->assertOutputSame( '',        function(){ dispatchExt('/00g'); });
		$this->assertOutputSame( '',        function(){ dispatchExt('/hi23'); });
	}

	public function test404TriggersOnce() {
		$this->expectOutputString( 'd404 Code' );

		respondExt( function(){ echo "d"; } );
		respondExt( '404', function(){ echo '404 Code'; } );
		dispatchExt( '/notroute' );
	}

	public function testStarRouteTriggers404() {
		$this->expectOutputString( 'c404 Code' );

		respondExt( '*', function(){ echo 'c'; });
		respondExt( '404', function(){ echo '404 Code'; } );
		dispatchExt( '/notroute' );
	}

	public function testNullRouteTriggers404() {
		$this->expectOutputString( 'c404 Code' );

		respondExt( function(){ echo 'c'; });
		respondExt( '404', function(){ echo '404 Code'; } );
		dispatchExt( '/notroute' );
	}

	public function testMethodSingle() {
		$this->expectOutputString( 'd' );

		respondExt( "GET /a", function(){ echo 'd'; });
		respondExt( "POST /a", function(){ echo 'e'; });
		dispatchExt( '/a' );
	}

	public function testMethodMultiple() {
		$this->expectOutputString( 'd' );

		respondExt( "GET|POST /a", function(){ echo 'd'; });
		dispatchExt( '/a' );
	}

	public function testgeturlExt() {
		$expect = "";

		respondExt('home', 'GET|POST /', function(){});
		respondExt('GET /users/', function(){});
		respondExt('users_show', 'GET /users/[i:id]', function(){});
		respondExt('users_do', 'POST /users/[i:id]/[delete|update:action]', function(){});
		respondExt('posts_do', 'GET /posts/[create|edit:action]?/[i:id]?', function(){});

		echo geturlExt('home'); echo "\n";
		$expect .= "/" . "\n";
		echo geturlExt('users_show', array('id' => 14)); echo "\n";
		$expect .= "/users/14" . "\n";
		echo geturlExt('users_do', array('id' => 17, 'action'=>'delete')); echo "\n";
		$expect .= "/users/17/delete" . "\n";
		echo geturlExt('posts_do', array('id' => 16)); echo "\n";
		$expect .= "/posts/16" . "\n";
		echo geturlExt('posts_do', array('action' => 'edit', 'id' => 15)); echo "\n";
		$expect .= "/posts/edit/15" . "\n";
		$this->expectOutputString( $expect );
	}

	public function testOptsParam() {
		$this->expectOutputString( "action=,id=16" );
		respondExt('users_do', 'GET /posts/[create|edit:action]?/[i:id]?', function($rq,$rs,$ap){echo "action=".$rq->param("action").",id=".$rq->param("id");});

		dispatchExt("/posts/16");
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

	public function testMethodCatchAll() {
		$this->expectOutputString( 'yup!123' );

		respondExt( 'POST', null, function($request){ echo 'yup!'; });
		respondExt( 'POST *', function($request){ echo '1'; });
		respondExt( 'POST /', function($request){ echo '2'; });
		respondExt( function($request){ echo '3'; });
		dispatchExt( '/', 'POST' );
	}

	public function testLazyTrailingMatch() {
		$this->expectOutputString( 'this-is-a-title-123' );

		respondExt( '/posts/[*:title][i:id]', function($request){
			echo $request->param('title')
				. $request->param('id');
		});
		dispatchExt( '/posts/this-is-a-title-123' );
	}

	public function testFormatMatch() {
		$this->expectOutputString( 'xml' );

		respondExt( '/output.[xml|json:format]', function($request){
			echo $request->param('format');
		});
		dispatchExt( '/output.xml' );
	}

	public function testDotSeparator() {
		$this->expectOutputString( 'matchA:slug=ABCD_E--matchB:slug=ABCD_E--' );

		respondExt('/[*:cpath]/[:slug].[:format]',   function($rq){ echo 'matchA:slug='.$rq->param("slug").'--';});
		respondExt('/[*:cpath]/[:slug].[:format]?',  function($rq){ echo 'matchB:slug='.$rq->param("slug").'--';});
		respondExt('/[*:cpath]/[a:slug].[:format]?', function($rq){ echo 'matchC:slug='.$rq->param("slug").'--';});
		dispatchExt("/category1/categoryX/ABCD_E.php");

		$this->assertOutputSame(
			'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
			function(){dispatchExt( '/category1/categoryX/ABCD_E.php' );}
		);
		$this->assertOutputSame(
			'matchB:slug=ABCD_E--',
			function(){dispatchExt( '/category1/categoryX/ABCD_E' );}
		);
	}

	public function testControllerActionStyleRouteMatch() {
		$this->expectOutputString( 'donkey-kick' );

		respondExt( '/[:controller]?/[:action]?', function($request){
			echo $request->param('controller')
				. '-' . $request->param('action');
		});
		dispatchExt( '/donkey/kick' );
	}

	public function testTrailingMatch() {
		respondExt( '/?[*:trailing]/dog/?', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ dispatchExt('/cat/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/cat/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/cat/ball/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('cat/ball/cheese/dog'); });
	}

	public function testTrailingPossessiveMatch() {
		respondExt( '/sub-dir/[**:trailing]', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ dispatchExt('/sub-dir/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/sub-dir/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/sub-dir/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ dispatchExt('/sub-dir/ball/cheese/dog'); });
	}

	public function testNSdispatchExt() {
		withExt('/u', function () {
			respondExt('GET /?',     function ($request, $response) { echo "slash";   });
			respondExt('GET /[:id]', function ($request, $response) { echo "id"; });
		});
		respondExt(404, function ($request, $response) { echo "404"; });

		$this->assertOutputSame("slash",          function(){dispatchExt("/u");});
		$this->assertOutputSame("slash",          function(){dispatchExt("/u/");});
		$this->assertOutputSame("id",             function(){dispatchExt("/u/35");});
		$this->assertOutputSame("404",             function(){dispatchExt("/35");});
	}

	public function testNSDispatchExternal() {
		$ext_namespaces = $this->loadExternalRoutes();

		respondExt(404, function ($request, $response) { echo "404"; });

		foreach ( $ext_namespaces as $namespace ) {
			$this->assertOutputSame('yup',  function() use ( $namespace ) { dispatchExt( $namespace . '/' ); });
			$this->assertOutputSame('yup',  function() use ( $namespace ) { dispatchExt( $namespace . '/testing/' ); });
		}
	}

	public function testNSDispatchExternalRerequired() {
		$ext_namespaces = $this->loadExternalRoutes();

		respondExt(404, function ($request, $response) { echo "404"; });

		foreach ( $ext_namespaces as $namespace ) {
			$this->assertOutputSame('yup',  function() use ( $namespace ) { dispatchExt( $namespace . '/' ); });
			$this->assertOutputSame('yup',  function() use ( $namespace ) { dispatchExt( $namespace . '/testing/' ); });
		}
	}

}
