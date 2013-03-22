<?php

Klein\respond( '/?', function( $request, $response, $app ) {
	echo 'yup';
});

Klein\respond( '/testing/?', function( $request, $response, $app ) {
	echo 'yup';
});
