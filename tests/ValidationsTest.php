<?php

require_once dirname(__FILE__) . '/AbstractKleinTest.php';

class ValidationsTest extends AbstractKleinTest {

	public function setUp() {
		parent::setUp();

        // Setup our error handler
        Klein\respond( function( $request, $response ) {
            $response->onError( array( $this, 'errorHandler' ) );
        } );
	}

    public function errorHandler( $response, $message, $type, $exception ) {
        if ( !is_null( $message ) && !empty( $message ) ) {
            echo $message;
        }
        else {
            echo 'fail';
        }
    }

    public function testCustomValidationMessage() {
        $custom_message = 'This is a custom error message...';

		Klein\respond( '/[:test_param]', function( $request ) use ( $custom_message ) {
			$request->validate( 'test_param', $custom_message )
			        ->notNull()
			        ->isLen( 0 );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( $custom_message, function(){ Klein\dispatch('/test'); });
    }

	public function testStringLengthExact() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isLen( 2 );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/ab'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test'); });
	}

	public function testStringLengthRange() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isLen( 3, 5 );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/dog'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/dogg'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/doggg'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/t'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/te'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/testin'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/testing'); });
	}

	public function testInt() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isInt();

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/2'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/12318935'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2.5'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2,5'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/~2'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2 5'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test'); });
	}

	public function testFloat() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isFloat();

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/2'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/2.5'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/3.14'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2.'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2,5'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/~2'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2 5'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test'); });
	}

	public function testEmail() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isEmail();

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/test@test.com'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/test@test.co.uk'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test@'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/2 5'); });
	}

	public function testAlpha() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isAlpha();

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/test'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/Test'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/TesT'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/test1'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/1test'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/@test'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/-test'); });
	}

	public function testAlnum() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isAlnum();

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/test'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/Test'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/TesT'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/test1'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/1test'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/@test'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/-test'); });
	}

	public function testContains() {
		klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->contains( 'dog' );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/bigdog'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/dogbig'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat-dog'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/catdogbear'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/DOG'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/doog'); });
	}

	public function testChars() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isChars( 'c-f' );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cdef'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cfed'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cf'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cdefg'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/dog'); });
	}

	public function testRegex() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isRegex( '/cat-[dog|bear|thing]/' );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat-dog'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat-bear'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat-thing'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cat'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cat-'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/dog-cat'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/catdog'); });
	}

	public function testNotRegex() {
		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->notRegex( '/cat-[dog|bear|thing]/' );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/cat-'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/dog-cat'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/catdog'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cat-dog'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cat-bear'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/cat-thing'); });
	}

	public function testCustomValidator() {
        // Add our custom validator
        Klein\addValidator( 'donkey', function( $string, $color ) {
            $regex_str = $color . '[-_]?donkey';

            return preg_match( '/' . $regex_str . '/', $string );
        });

		Klein\respond( '/[:test_param]', function( $request ) {
			$request->validate( 'test_param' )
			        ->notNull()
			        ->isDonkey( 'brown' );

            // We should only get here if we passed our validations
            echo 'yup!';
		} );

		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/browndonkey'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/brown-donkey'); });
		$this->assertOutputSame( 'yup!', function(){ Klein\dispatch('/brown_donkey'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/bluedonkey'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/blue-donkey'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/blue_donkey'); });
		$this->assertOutputSame( 'fail', function(){ Klein\dispatch('/brown_donk'); });
	}

}
