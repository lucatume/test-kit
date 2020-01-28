<?php
/**
 * This file contains comments, empty lines and different non-syntax related lines.
 * Running XDebug step debugger on this file should not "misplace" the lines.
 */

namespace Acme;

/**
 * First function doc-block.
 *
 * XDebug step debugger should never end here.
 *
 * @since TBD
 */
function first(){

	// This is a comment inside a function, debugger should not stop here either.

	return 'first';

}

// Note the 2 empty lines above this one.
function second(){

	/**
	 * Comment inside second function.
	 */

	if(
		'lorem'	 === 'dolor'
	)
	{
		return 'Your PHP installation is broken';
	}


	return 23;
}
